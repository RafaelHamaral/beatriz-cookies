<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreContact;
use App\Services\OrderWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function checkout(): array
    {
        $first = Product::factory()->create(['name' => "Cookie de maçã & canela\nartesanal", 'price_cents' => 1250]);
        $second = Product::factory()->create(['name' => 'Brownie 70% + nozes', 'price_cents' => 1800]);
        $this->put(route('cart.update', $first->id), ['quantity' => 2]);
        $this->put(route('cart.update', $second->id), ['quantity' => 1]);
        $this->get(route('checkout.create'))->assertOk();

        return ['checkout_key' => session('checkout_key'), 'customer_name' => 'João & Ana + Família',
            'customer_email' => 'cliente@example.com', 'customer_phone' => '11999999999',
            'notes' => "Sem laço & com cartão + coração ❤️\nAvisar ao chegar #2"];
    }

    public function test_saved_order_opens_whatsapp_once_and_reopening_never_duplicates_order_or_email(): void
    {
        StoreContact::create(['whatsapp' => '5511999999999']);
        $payload = $this->checkout();
        $this->get(route('checkout.create'))->assertSee('Enviar pedido pelo WhatsApp')
            ->assertDontSee('http-equiv="refresh"', false);
        $this->post(route('checkout.store'), $payload + ['total_cents' => 1, 'shipping_cents' => 100, 'payment_method' => 'pix'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::with('items')->sole();
        $this->assertSame(4300, $order->total_cents);
        $url = app(OrderWhatsApp::class)->url($order);
        $this->assertStringStartsWith('https://wa.me/5511999999999?text=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertCount(1, $query);
        $this->assertSame("Olá! Gostaria de confirmar meu pedido no Site Beatriz.\nPedido: #{$order->number}\nCliente: João & Ana + Família\n\nItens:\n- 2 × Cookie de maçã & canela artesanal — R$ 12,50 por unidade; subtotal R$ 25,00\n- 1 × Brownie 70% + nozes — R$ 18,00 por unidade; subtotal R$ 18,00\n\nTotal dos produtos: R$ 43,00\n\nObservações:\nSem laço & com cartão + coração ❤️\nAvisar ao chegar #2", $query['text']);
        $this->assertStringNotContainsString('cliente@example.com', $query['text']);
        $this->assertStringNotContainsString('11999999999', $query['text']);
        $this->assertStringNotContainsString('pix', $query['text']);
        $first = $this->get(route('checkout.success', $order->number))->assertOk()
            ->assertSee('http-equiv="refresh"', false)->assertSee(e($url), false)
            ->assertSee('Abrir WhatsApp')->assertSee('Seu pedido foi registrado')
            ->assertSee('somente quando você revisar e tocar');
        $this->assertStringContainsString("script-src 'none'", $first->headers->get('Content-Security-Policy'));
        $this->get(route('checkout.success', $order->number))->assertOk()
            ->assertDontSee('http-equiv="refresh"', false)->assertSee(e($url), false);
        $this->post(route('checkout.store'), $payload)->assertRedirect(route('checkout.success', $order->number));
        $this->get(route('checkout.success', $order->number))->assertDontSee('http-equiv="refresh"', false);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('store_notification_deliveries', 1);
        $this->assertSame([], session('cart', []));
    }

    public function test_link_uses_saved_snapshots_and_current_store_number_and_is_private(): void
    {
        StoreContact::create(['whatsapp' => '5511999999999']);
        $this->post(route('checkout.store'), $this->checkout())->assertSessionHasNoErrors();
        $order = Order::sole();
        Product::query()->update(['name' => 'Novo nome', 'price_cents' => 99999]);
        StoreContact::query()->update(['whatsapp' => '5521999999999']);
        $url = app(OrderWhatsApp::class)->url($order);
        $this->assertStringStartsWith('https://wa.me/5521999999999?text=', $url);
        $message = rawurldecode(explode('?text=', $url, 2)[1]);
        $this->assertStringContainsString('Cookie de maçã & canela', $message);
        $this->assertStringContainsString('R$ 43,00', $message);
        $this->assertStringNotContainsString('Novo nome', $message);
        $this->withSession(['order_receipts' => []])->get(route('checkout.success', $order->number))->assertNotFound();
    }

    #[DataProvider('invalidDestinations')]
    public function test_missing_or_invalid_destination_preserves_order_and_email_without_a_broken_link(?string $number): void
    {
        if ($number !== null) {
            StoreContact::create(['whatsapp' => $number]);
        }
        $payload = $this->checkout();
        $this->get(route('checkout.create'))->assertSee('Registrar pedido')->assertSee('WhatsApp da loja está indisponível');
        $this->post(route('checkout.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $this->get(route('checkout.success', $order->number))->assertOk()->assertSee('Seu pedido está salvo')
            ->assertDontSee('Abrir WhatsApp')->assertDontSee('http-equiv="refresh"', false)->assertDontSee('https://wa.me/');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('store_notification_deliveries', 1);
    }

    public static function invalidDestinations(): array
    {
        return [
            'absent' => [null], 'blank' => [''], 'zero prefix' => ['05511999999999'],
            'too short' => ['5511'], 'too long' => ['5511999999999999'],
            'formatted' => ['+55 (11) 99999-9999'], 'injection' => ['5511999999999?text=wrong'],
            'trailing newline' => ["5511999999999\n"],
        ];
    }

    public function test_unsaved_order_and_failed_validation_never_generate_a_link(): void
    {
        StoreContact::create(['whatsapp' => '5511999999999']);
        $this->assertNull(app(OrderWhatsApp::class)->url(new Order));
        $this->post(route('checkout.store'), [])->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('store_notification_deliveries', 0);
        $this->assertNull(session('open_order_whatsapp'));
    }

    public function test_message_omits_empty_notes_and_unavailable_delivery_payment_and_variation_fields(): void
    {
        $payload = $this->checkout();
        $payload['notes'] = '';
        $this->post(route('checkout.store'), $payload)->assertSessionHasNoErrors();
        $message = app(OrderWhatsApp::class)->message(Order::sole());
        foreach (['Observações:', 'Endereço:', 'Frete:', 'Pagamento:', 'Variação:', 'Entrega:', 'Retirada:'] as $label) {
            $this->assertStringNotContainsString($label, $message);
        }
    }
}
