<?php

namespace Tests\Feature;

use App\Models\AboutSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_publish_text_and_image_replace_and_remove_it(): void
    {
        Storage::fake('catalog');
        $this->get(route('home'))->assertOk()->assertSee('Sobre a Beatriz');
        $this->actingAs(User::factory()->editor()->create());
        $this->get(route('about.edit'))->assertOk();
        $data = ['title' => 'Nossa loja', 'body' => '<script>História</script>', 'image_alt' => 'Nossa cozinha', 'is_active' => 1];
        $this->put(route('about.update'), $data + ['image' => UploadedFile::fake()->image('loja.jpg', 800, 600)])
            ->assertSessionHasNoErrors()->assertRedirect(route('about.edit'));
        $firstPath = AboutSection::current()->image_path;
        Storage::disk('catalog')->assertExists($firstPath);
        $this->get(route('home'))->assertSee('&lt;script&gt;História&lt;/script&gt;', false)->assertDontSee('<script>História</script>', false);
        $this->get(route('about.image'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->put(route('about.update'), $data + ['image' => UploadedFile::fake()->image('nova.png', 600, 600)])->assertSessionHasNoErrors();
        Storage::disk('catalog')->assertMissing($firstPath);
        $secondPath = AboutSection::current()->image_path;
        $this->put(route('about.update'), $data + ['remove_image' => 1])->assertSessionHasNoErrors();
        Storage::disk('catalog')->assertMissing($secondPath);
        $this->get(route('about.image'))->assertNotFound();
        $this->put(route('about.update'), array_replace($data, ['is_active' => 0]))->assertSessionHasNoErrors();
        $this->get(route('home'))->assertDontSee('Nossa loja');
    }

    public function test_multiple_images_are_appended_served_and_removed_individually(): void
    {
        Storage::fake('catalog');
        $this->actingAs(User::factory()->editor()->create());
        $data = ['title' => 'Sobre', 'body' => 'Texto fixo', 'is_active' => 1];
        $this->put(route('about.update'), $data + ['images' => [
            UploadedFile::fake()->image('primeira.jpg', 600, 600),
            UploadedFile::fake()->image('segunda.jpg', 600, 600),
        ]])->assertSessionHasNoErrors();
        $gallery = AboutSection::current()->gallery();
        $this->assertCount(2, $gallery);
        $this->get(route('home'))->assertOk()->assertSee('about-slides--animated')->assertSee('Pausar transição')->assertSee('Texto fixo');
        $key = array_key_first($gallery);
        $this->get(route('about.image', ['image' => $key]))->assertOk();
        $this->get(route('about.image', ['image' => 'inexistente']))->assertNotFound();
        $this->put(route('about.update'), $data + ['remove_images' => [$key]])->assertSessionHasNoErrors();
        Storage::disk('catalog')->assertMissing($gallery[$key]['path']);
        $this->get(route('home'))->assertDontSee('about-slides--animated');
        $this->assertCount(1, AboutSection::current()->gallery());
        $this->put(route('about.update'), array_replace($data, ['is_active' => 0]))->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('about.image', ['image' => array_key_first(AboutSection::current()->gallery())]))->assertNotFound();
    }

    public function test_three_images_have_distinct_urls_and_synchronized_animation_including_reduced_motion(): void
    {
        AboutSection::query()->forceCreate([
            'id' => 1, 'title' => 'Sobre', 'body' => 'História', 'is_active' => true,
            'images' => [
                'first' => ['path' => 'products/first.jpg', 'mime' => 'image/jpeg'],
                'second' => ['path' => 'products/second.jpg', 'mime' => 'image/jpeg'],
                'third' => ['path' => 'products/third.jpg', 'mime' => 'image/jpeg'],
            ],
        ]);
        $response = $this->get(route('home'))->assertOk();
        foreach (['first', 'second', 'third'] as $key) {
            $response->assertSee(route('about.image', ['image' => $key]));
        }
        $response->assertSee('--duration: 6s', false)
            ->assertSee('--delay: 0s', false)
            ->assertSee('--delay: -4s', false)
            ->assertSee('--delay: -2s', false)
            ->assertSee('@keyframes about-step', false)
            ->assertSee('store.css?v=', false);
    }

    public function test_guests_and_viewers_cannot_configure_about_and_invalid_uploads_are_rejected(): void
    {
        $this->get(route('about.edit'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->viewer()->create());
        $this->get(route('about.edit'))->assertForbidden();
        $this->put(route('about.update'), [])->assertForbidden();
        $this->actingAs(User::factory()->editor()->create());
        $this->put(route('about.update'), [
            'title' => 'Sobre', 'body' => 'Texto', 'is_active' => 1,
            'image' => UploadedFile::fake()->create('arquivo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('about_sections', 0);
    }
}
