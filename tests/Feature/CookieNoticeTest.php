<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_and_footer_review_show_only_an_essential_notice(): void
    {
        $this->get('/')->assertOk()->assertSee('id="aviso-cookies"', false)
            ->assertSee('Entendi')->assertSee('Preferências de cookies')
            ->assertDontSee('Aceitar todos')->assertDontSee('Recusar opcionais')
            ->assertSee('cookies=1#aviso-cookies', false);

        $this->withCookie('beatriz_cookie_notice', 'v1');
        $this->get('/')->assertDontSee('id="aviso-cookies"', false)->assertSee('Preferências de cookies');
        $this->get('/catalogo')->assertDontSee('id="aviso-cookies"', false);
        $this->get('/?cookies=1')->assertSee('id="aviso-cookies"', false)->assertSee('Entendi');
    }

    public function test_acknowledgement_preserves_login_and_cart_and_sets_a_persistent_cookie(): void
    {
        $user = User::factory()->editor()->create();
        $response = $this->actingAs($user)->withSession(['cart' => [12 => 2]])
            ->post(route('cookies.acknowledge'), ['return_path' => '/catalogo']);

        $response->assertRedirect('/catalogo')->assertCookie('beatriz_cookie_notice', 'v1')
            ->assertSessionHas('cart', [12 => 2]);
        $this->assertAuthenticatedAs($user);
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'beatriz_cookie_notice');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertGreaterThan(time() + 179 * 86400, $cookie->getExpiresTime());
        $this->assertStringContainsString("script-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_invalid_acknowledgement_and_unsafe_redirects_are_handled(): void
    {
        $this->withCookie('beatriz_cookie_notice', 'unknown')->get('/')
            ->assertSee('id="aviso-cookies"', false);

        foreach (['https://example.com', '//example.com', '/\\example.com', "\r\nLocation: bad", ['bad']] as $path) {
            $this->post(route('cookies.acknowledge'), ['return_path' => $path])->assertRedirect('/');
        }
    }
}
