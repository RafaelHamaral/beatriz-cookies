<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('catalog');
    }

    public function test_storefront_lists_only_active_products_from_active_categories(): void
    {
        $visible = Product::factory()->create([
            'name' => 'Cookie visível',
            'description' => 'Descrição com <script>alert(1)</script>',
            'is_available' => false,
        ]);
        $hidden = Product::factory()->inactive()->create(['name' => 'Cookie oculto']);
        $orphaned = Product::factory()->create([
            'name' => 'Cookie sem categoria ativa',
            'category_id' => Category::factory()->inactive(),
        ]);

        $this->get('/')->assertOk()->assertSee('Cookie visível')->assertDontSee('Cookie oculto')->assertDontSee('Cookie sem categoria ativa');
        $this->get(route('catalog.index'))->assertOk()->assertSee('Cookie visível')->assertSee('Indisponível')->assertDontSee('Cookie oculto');
        $this->get(route('products.show', $visible))->assertOk()->assertSee('Indisponível')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('products.show', $hidden))->assertNotFound();
        $this->get(route('products.show', $orphaned))->assertNotFound();
        $this->get(route('catalog.category', $orphaned->category))->assertNotFound();
    }

    public function test_editor_creates_a_product_with_exact_price_and_server_named_cover(): void
    {
        $editor = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $first = $this->image(500, 420, 'jpeg', 'foto-cliente.jpg');
        $second = $this->image(480, 480, 'png');
        $polyglot = $this->image(500, 500, 'jpeg');
        file_put_contents($polyglot->getRealPath(), file_get_contents($polyglot->getRealPath()).'<?php echo "hack";');

        $this->actingAs($editor)->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Cookie de chocolate',
            'description' => 'Massa amanteigada',
            'ingredients' => 'Farinha e cacau',
            'allergens' => 'Contém leite',
            'price' => '19,90',
            'is_available' => '1',
            'is_active' => '1',
            'cover' => 'new:1',
            'images' => [$first, $polyglot],
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->where('name', 'Cookie de chocolate')->first();
        $this->assertNotNull($product);
        $this->assertSame(1990, $product->price_cents);
        $this->assertCount(2, $product->images);
        $cover = $product->images()->where('is_cover', true)->first();
        $this->assertNotNull($cover);
        $this->assertSame('image/jpeg', $cover->mime);
        $this->assertMatchesRegularExpression('/^products\/[0-9a-f-]{36}\.jpg$/', $cover->path);
        $this->assertStringNotContainsString('foto-cliente', $cover->path);
        $this->assertStringNotContainsString('<?php', Storage::disk('catalog')->get($cover->path));

        $this->actingAs($editor)->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Meio cookie',
            'description' => 'Pequeno',
            'ingredients' => 'Açúcar',
            'price' => '10.5',
            'is_available' => '1',
            'is_active' => '1',
            'images' => [$second],
        ])->assertRedirect(route('products.index'));

        $this->assertSame(1050, Product::query()->where('name', 'Meio cookie')->value('price_cents'));
        $png = ProductImage::query()->where('mime', 'image/png')->first();
        $this->assertNotNull($png);
        $this->get(route('products.image', $png))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_invalid_uploads_and_prices_are_rejected(): void
    {
        $editor = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $payload = [
            'category_id' => $category->id,
            'name' => 'Cookie rejeitado',
            'description' => 'Teste',
            'ingredients' => 'Farinha',
            'is_available' => '1',
            'is_active' => '1',
            'price' => '18,50',
        ];

        $this->actingAs($editor)->post(route('products.store'), $payload + [
            'images' => [$this->raw('<?php echo 1;', 'foto.jpg')],
        ])->assertSessionHasErrors('images.0');

        $this->actingAs($editor)->post(route('products.store'), $payload + [
            'images' => [$this->image(80, 80, 'gif')],
        ])->assertSessionHasErrors('images.0');

        $this->actingAs($editor)->post(route('products.store'), $payload + [
            'images' => [$this->image(120, 120)],
        ])->assertSessionHasErrors('images.0');

        config(['catalog.max_edge' => 450]);
        $this->actingAs($editor)->post(route('products.store'), $payload + [
            'images' => [$this->image(500, 500)],
        ])->assertSessionHasErrors('images.0');

        config(['catalog.max_edge' => 4000, 'catalog.max_bytes' => 300]);
        $this->actingAs($editor)->post(route('products.store'), $payload + [
            'images' => [$this->image(400, 400)],
        ])->assertSessionHasErrors('images.0');

        config(['catalog.max_bytes' => 5 * 1024 * 1024]);
        $this->actingAs($editor)->post(route('products.store'), array_merge($payload, [
            'price' => '10.555',
            'images' => [$this->image(400, 400)],
        ]))->assertSessionHasErrors('price');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('catalog')->allFiles());
    }

    public function test_webp_is_accepted_and_permissions_follow_the_role(): void
    {
        config(['catalog.min_edge' => 1]);
        $editor = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Clássicos']);

        $this->actingAs($viewer)->get(route('products.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('categories.store'), [
            'name' => 'Bloqueada',
            'is_active' => '1',
        ])->assertForbidden();

        $this->actingAs($editor)->post(route('categories.store'), [
            'name' => 'Novidades',
            'description' => 'Da semana',
            'sort_order' => 2,
            'is_active' => '1',
        ])->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Novidades', 'is_active' => true]);

        $this->actingAs($editor)->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Cookie webp',
            'description' => 'Formato webp',
            'ingredients' => 'Cacau',
            'price' => '8,00',
            'is_available' => '1',
            'is_active' => '1',
            'images' => [$this->raw(base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'), 'foto.webp')],
        ])->assertRedirect(route('products.index'));

        $image = ProductImage::query()->first();
        $this->assertSame('image/webp', $image->mime);
        $this->assertStringEndsWith('.webp', $image->path);

        $product = $image->product;
        $this->actingAs($admin)->get(route('products.index'))->assertRedirect(route('two-factor.setup'));
        $this->actingAs($this->confirmTwoFactor($admin))->get(route('products.index'))->assertOk();
        $this->actingAs($editor)->patch(route('products.activation', $product), [
            'is_active' => '0',
        ])->assertRedirect(route('products.index'));

        $this->get(route('products.show', $product))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get(route('products.image', $image))->assertNotFound();
        $this->actingAs($editor)->get(route('products.image', $image))->assertOk();
        $this->get('/')->assertDontSee('Cookie webp');
    }

    private function image(int $width, int $height, string $format = 'jpeg', string $name = 'foto.jpg'): UploadedFile
    {
        $canvas = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($canvas, 180, 110, 70);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $color);
        $path = tempnam(sys_get_temp_dir(), 'cookie');

        if ($format === 'png') {
            imagepng($canvas, $path);
            $name = str_ends_with($name, '.png') ? $name : 'foto.png';
        } elseif ($format === 'gif') {
            imagegif($canvas, $path);
            $name = 'foto.gif';
        } else {
            imagejpeg($canvas, $path, 90);
        }

        imagedestroy($canvas);

        return new UploadedFile($path, $name, null, UPLOAD_ERR_OK, true);
    }

    private function raw(string $contents, string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'raw');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, UPLOAD_ERR_OK, true);
    }
}
