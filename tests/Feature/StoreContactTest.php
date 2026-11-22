<?php

namespace Tests\Feature;

use App\Models\StoreContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_publish_and_clear_contact_details(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $this->get(route('store-contact.edit'))->assertOk();
        $data = [
            'phone' => '(11) 99999-9999', 'email' => 'loja@example.com',
            'address' => "Rua das Flores, 123\nSão Paulo — SP",
            'whatsapp' => '5511999999999', 'instagram' => 'https://www.instagram.com/loja',
            'facebook' => 'https://www.facebook.com/loja', 'tiktok' => 'https://www.tiktok.com/@loja',
        ];
        $this->put(route('store-contact.update'), $data)->assertSessionHasNoErrors()->assertRedirect(route('store-contact.edit'));
        $this->assertDatabaseHas('store_contacts', ['id' => 1] + $data);
        $this->assertDatabaseHas('audit_logs', ['action' => 'store_contact.updated']);
        foreach (['home', 'contact.create', 'catalog.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Rua das Flores, 123')
                ->assertSee('tel:11999999999', false)->assertSee('https://wa.me/5511999999999', false)
                ->assertSee($data['instagram'])->assertSee('mailto:loja@example.com', false);
        }
        $this->put(route('store-contact.update'), [])->assertSessionHasNoErrors();
        $this->assertNull(StoreContact::current()->phone);
        $this->get(route('home'))->assertDontSee('wa.me/')->assertDontSee('Rua das Flores, 123');
    }

    public function test_permissions_and_validation_protect_store_contact(): void
    {
        $this->get(route('store-contact.edit'))->assertRedirect(route('login'));
        $this->put(route('store-contact.update'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->viewer()->create());
        $this->get(route('store-contact.edit'))->assertForbidden();
        $this->put(route('store-contact.update'), [])->assertForbidden();
        $this->actingAs(User::factory()->editor()->create());
        $this->put(route('store-contact.update'), [
            'instagram' => 'javascript:alert(1)', 'facebook' => 'http://facebook.com/loja',
            'tiktok' => 'invalid', 'whatsapp' => 'abc', 'email' => 'invalid',
        ])->assertSessionHasErrors(['instagram', 'facebook', 'tiktok', 'whatsapp', 'email']);
        $this->assertDatabaseCount('store_contacts', 0);
        $this->put(route('store-contact.update'), ['address' => '<script>texto</script>'])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertDontSee('<script>texto</script>', false)->assertSee('&lt;script&gt;texto&lt;/script&gt;', false);
    }
}
