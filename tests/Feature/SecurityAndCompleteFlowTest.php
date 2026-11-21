<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndCompleteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_and_sensitive_cache_rules_are_applied(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));

        foreach (['login', 'contact.create', 'reviews.create'] as $route) {
            $sensitive = $this->get(route($route));
            $this->assertStringContainsString('no-store', (string) $sensitive->headers->get('Cache-Control'));
        }

        config(['app.env' => 'production']);
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_every_state_changing_web_route_uses_the_csrf_protected_stack(): void
    {
        $this->assertContains(
            ValidateCsrfToken::class,
            app(Middleware::class)->getMiddlewareGroups()['web'],
        );

        foreach (app('router')->getRoutes() as $route) {
            $changesState = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];
            $usesApplicationController = str_starts_with($route->getActionName(), 'App\\');
            if (! $changesState || ! $usesApplicationController) {
                continue;
            }

            $this->assertContains(
                'web',
                $route->gatherMiddleware(),
                "A rota {$route->uri()} precisa usar o grupo web com proteção CSRF.",
            );
        }
    }

    public function test_complete_store_operation_flow_reaches_the_dashboard_and_public_review(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cookie fluxo completo',
            'price_cents' => 1850,
        ]);

        $this->put(route('cart.update', $product->id), ['quantity' => 2])->assertRedirect();
        $this->get(route('checkout.create'))->assertOk();
        $this->post(route('checkout.store'), [
            'checkout_key' => session('checkout_key'),
            'customer_name' => 'Cliente completo',
            'customer_email' => 'cliente-completo@example.com',
            'customer_phone' => '11999999999',
            'notes' => 'Retirada na loja',
        ])->assertRedirect();

        $order = Order::query()->with('items')->sole();
        $this->assertSame(3700, $order->total_cents);
        $this->assertSame(1850, $order->items->sole()->unit_price_cents);
        $this->get(route('checkout.success', $order->number))->assertOk();

        $this->post(route('contact.store'), [
            'name' => 'Cliente completo',
            'email' => 'cliente-completo@example.com',
            'phone' => '11999999999',
            'subject' => 'Dúvida sobre retirada',
            'message' => 'Qual é o horário disponível?',
        ])->assertRedirect();
        $this->post(route('reviews.store'), [
            'name' => 'Cliente completo',
            'rating' => 5,
            'comment' => 'Experiência completa aprovada.',
        ])->assertRedirect();

        $admin = $this->confirmTwoFactor(User::factory()->admin()->create());
        $this->actingAs($admin);
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'confirmed'])->assertRedirect();
        $this->patch(route('orders.payment', $order), ['version' => 1, 'status' => 'paid'])->assertRedirect();
        $this->patch(route('orders.update', $order), ['version' => 2, 'status' => 'preparing'])->assertRedirect();
        $this->patch(route('orders.update', $order), ['version' => 3, 'status' => 'ready'])->assertRedirect();
        $this->patch(route('orders.update', $order), ['version' => 4, 'status' => 'completed'])->assertRedirect();

        $message = ContactMessage::query()->sole();
        $this->get(route('messages.show', $message))->assertOk();
        $this->patch(route('messages.update', $message), ['version' => 1, 'status' => 'handled'])->assertRedirect();

        $review = Review::query()->sole();
        $this->patch(route('reviews.update', $review), ['version' => 0, 'status' => 'approved'])->assertRedirect();

        $today = now()->toDateString();
        $this->get(route('dashboard', ['from' => $today, 'to' => $today]))
            ->assertOk()
            ->assertSee('R$ 37,00')
            ->assertSee('Cookie fluxo completo')
            ->assertSee('>2</strong>', false);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Experiência completa aprovada.');

        $order->refresh();
        $this->assertSame('completed', $order->fulfillment_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($admin->id, $order->paid_by);
        $this->assertNotNull($message->fresh()->handled_at);
        $this->assertSame('approved', $review->fresh()->status);

        $auditProperties = AuditLog::query()->pluck('properties')->toJson();
        $this->assertStringNotContainsString('cliente-completo@example.com', $auditProperties);
        $this->assertStringNotContainsString('Qual é o horário disponível?', $auditProperties);
        $this->assertStringNotContainsString('Experiência completa aprovada.', $auditProperties);

        $this->post(route('logout'));
        $this->get(route('orders.show', $order))->assertRedirect('/login');
        $this->get(route('messages.show', $message))->assertRedirect('/login');
    }
}
