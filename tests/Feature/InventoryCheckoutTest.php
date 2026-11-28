<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_loose_sale_deducts_one_physical_unit(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);

        $this->buy([$cookie->id => 1]);

        $this->assertSame(19, $cookie->fresh()->stock_units);
        $this->assertSame(1, Order::sole()->stockMovements()->where('product_id', $cookie->id)->value('units'));
    }

    public function test_two_combos_of_three_deduct_six_units(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);
        $combo = Product::factory()->create(['name' => 'Combo de 3', 'stock_units' => 80]);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);

        $this->buy([$combo->id => 2]);

        $this->assertSame(14, $cookie->fresh()->stock_units);
        $this->assertSame(80, $combo->fresh()->stock_units);
        $this->assertSame(6, Order::sole()->stockMovements()->where('product_id', $cookie->id)->value('units'));
    }

    public function test_two_combos_of_three_plus_one_loose_leave_thirteen(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);
        $combo = Product::factory()->create(['name' => 'Combo de 3', 'stock_units' => 80]);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);

        $this->buy([$combo->id => 2, $cookie->id => 1]);

        $order = Order::sole();
        $this->assertSame(13, $cookie->fresh()->stock_units);
        $this->assertSame(80, $combo->fresh()->stock_units);
        $this->assertSame(7, $order->stockMovements()->where('product_id', $cookie->id)->value('units'));
        $this->assertSame(1, $order->stockMovements()->count());
    }

    public function test_editor_configures_a_mixed_combo_and_checkout_deducts_each_flavor(): void
    {
        $chocolate = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);
        $vanilla = Product::factory()->create(['name' => 'Cookie de baunilha', 'stock_units' => 10]);
        $combo = Product::factory()->create(['name' => 'Combo misto', 'stock_units' => 0]);
        $image = ProductImage::query()->create([
            'product_id' => $combo->id,
            'public_id' => (string) Str::uuid(),
            'path' => 'products/combo.jpg',
            'mime' => 'image/jpeg',
            'width' => 800,
            'height' => 800,
            'is_cover' => true,
            'sort_order' => 0,
        ]);
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route('products.edit', $combo))
            ->assertOk()
            ->assertSee('Estoque em unidades')
            ->assertSee('Composição do combo')
            ->assertSee('Cookie de chocolate')
            ->assertSee('Cookie de baunilha');

        $this->actingAs($editor)->put(route('products.update', $combo), [
            'category_id' => $combo->category_id,
            'name' => $combo->name,
            'description' => $combo->description,
            'ingredients' => $combo->ingredients,
            'allergens' => $combo->allergens,
            'price' => '25,00',
            'is_available' => '1',
            'is_active' => '1',
            'cover' => 'existing:'.$image->id,
            'stock_units' => 50,
            'components' => [
                ['product_id' => $chocolate->id, 'units' => 2],
                ['product_id' => $vanilla->id, 'units' => 1],
            ],
        ])->assertRedirect(route('products.edit', $combo));

        $this->assertSame(50, $combo->fresh()->stock_units);
        $this->assertDatabaseHas('product_components', ['combo_id' => $combo->id, 'product_id' => $chocolate->id, 'units' => 2]);
        $this->assertDatabaseHas('product_components', ['combo_id' => $combo->id, 'product_id' => $vanilla->id, 'units' => 1]);

        $this->actingAs($editor)->put(route('products.update', $combo), [
            'category_id' => $combo->category_id,
            'name' => $combo->name,
            'description' => $combo->description,
            'ingredients' => $combo->ingredients,
            'allergens' => $combo->allergens,
            'price' => '25,00',
            'is_available' => '1',
            'is_active' => '1',
            'cover' => 'existing:'.$image->id,
            'stock_units' => 50,
            'components' => [
                ['product_id' => $combo->id, 'units' => 1],
            ],
        ])->assertSessionHasErrors('components.0.product_id');

        $this->app['auth']->forgetGuards();
        $this->get(route('products.show', $combo))->assertOk()->assertSee('2 × Cookie de chocolate')->assertSee('1 × Cookie de baunilha');

        $this->buy([$combo->id => 1]);

        $this->assertSame(18, $chocolate->fresh()->stock_units);
        $this->assertSame(9, $vanilla->fresh()->stock_units);
        $this->assertSame(50, $combo->fresh()->stock_units);
        $order = Order::sole();
        $this->assertSame(2, $order->stockMovements()->where('product_id', $chocolate->id)->value('units'));
        $this->assertSame(1, $order->stockMovements()->where('product_id', $vanilla->id)->value('units'));
    }

    public function test_insufficient_stock_names_the_item_and_keeps_the_cart(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 6]);
        $combo = Product::factory()->create(['name' => 'Combo de 3']);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);
        $this->put(route('cart.update', $combo->id), ['quantity' => 2]);
        $this->put(route('cart.update', $cookie->id), ['quantity' => 1]);
        $this->get(route('checkout.create'))->assertOk();

        $this->from(route('checkout.create'))
            ->followingRedirects()
            ->post(route('checkout.store'), $this->customer((string) session('checkout_key')))
            ->assertSee('Cookie de chocolate está indisponível: o pedido precisa de 7 unidades e há 6.')
            ->assertSee('Revisar carrinho');

        $this->assertNotEmpty(session('cart'));
        $this->assertSame(0, Order::count());
        $this->assertSame(6, $cookie->fresh()->stock_units);
        $this->assertDatabaseCount('order_stock_movements', 0);
    }

    public function test_resubmit_and_reopening_whatsapp_do_not_deduct_again(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);
        $combo = Product::factory()->create(['name' => 'Combo de 3', 'stock_units' => 80]);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);
        $this->put(route('cart.update', $combo->id), ['quantity' => 2]);
        $this->put(route('cart.update', $cookie->id), ['quantity' => 1]);
        $this->get(route('checkout.create'))->assertOk();
        $cart = session('cart');
        $key = (string) session('checkout_key');
        $quote = session('checkout_quote');
        $payload = $this->customer($key);

        $this->post(route('checkout.store'), $payload)->assertRedirect();
        $order = Order::sole();
        $this->assertSame(13, $cookie->fresh()->stock_units);

        $this->post(route('checkout.store'), $payload)->assertRedirect(route('checkout.success', $order->number));
        $this->assertSame(13, $cookie->fresh()->stock_units);
        $this->assertSame(1, Order::count());

        session()->forget('order_receipts');
        session()->put('checkout_key', $key);
        session()->put('cart', $cart);
        session()->put('checkout_quote', $quote);
        $this->post(route('checkout.store'), $payload)->assertRedirect(route('checkout.success', $order->number));

        $this->get(route('checkout.success', $order->number))->assertOk();
        $this->get(route('checkout.success', $order->number))->assertOk();

        $this->assertSame(13, $cookie->fresh()->stock_units);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $order->stockMovements()->count());
        $this->assertSame(7, $order->stockMovements()->first()->units);
    }

    public function test_a_second_buyer_cannot_take_units_already_sold(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 7]);
        $this->assertTrue(app(Inventory::class)->take($cookie->id, 7));
        $this->assertFalse(app(Inventory::class)->take($cookie->id, 7));
        $this->assertSame(0, $cookie->fresh()->stock_units);

        $combo = Product::factory()->create(['name' => 'Combo de 3']);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);
        Product::query()->whereKey($cookie->id)->update(['stock_units' => 7]);

        $this->buy([$combo->id => 2, $cookie->id => 1]);
        $this->assertSame(0, $cookie->fresh()->stock_units);

        session()->flush();
        $this->put(route('cart.update', $cookie->id), ['quantity' => 1]);
        $this->get(route('checkout.create'))->assertOk();
        $this->from(route('checkout.create'))
            ->post(route('checkout.store'), $this->customer((string) session('checkout_key')))
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, $cookie->fresh()->stock_units);
        $this->assertSame(1, Order::count());
        $this->assertNotEmpty(session('cart'));
    }

    public function test_cancellation_restores_the_recorded_units_once_even_if_the_combo_changes(): void
    {
        $cookie = Product::factory()->create(['name' => 'Cookie de chocolate', 'stock_units' => 20]);
        $combo = Product::factory()->create(['name' => 'Combo de 3', 'stock_units' => 80]);
        $combo->components()->create(['product_id' => $cookie->id, 'units' => 3]);
        $this->buy([$combo->id => 2, $cookie->id => 1]);
        $order = Order::sole();
        $this->assertSame(13, $cookie->fresh()->stock_units);

        $combo->components()->update(['units' => 9]);
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->patch(route('orders.update', $order), [
            'version' => $order->version,
            'status' => 'cancelled',
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('cancelled', $order->fulfillment_status);
        $this->assertNotNull($order->stock_restored_at);
        $this->assertSame(20, $cookie->fresh()->stock_units);
        $this->assertSame(7, $order->stockMovements()->value('units'));

        $this->actingAs($editor)->patch(route('orders.update', $order), [
            'version' => $order->version,
            'status' => 'cancelled',
        ])->assertSessionHasErrors('status');

        app(Inventory::class)->restore($order);
        $order->save();

        $this->assertSame(20, $cookie->fresh()->stock_units);
        $this->assertSame(80, $combo->fresh()->stock_units);
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function buy(array $quantities): void
    {
        foreach ($quantities as $id => $quantity) {
            $this->put(route('cart.update', $id), ['quantity' => $quantity])->assertRedirect();
        }
        $this->get(route('checkout.create'))->assertOk();
        $this->post(route('checkout.store'), $this->customer((string) session('checkout_key')))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    /**
     * @return array<string, string>
     */
    private function customer(string $key): array
    {
        return [
            'checkout_key' => $key,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '11988887777',
        ];
    }
}
