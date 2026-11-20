<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_uses_server_prices_and_preserves_snapshots_and_is_idempotent(): void
    {
        $a = Product::factory()->create(['price_cents' => 1099]);
        $b = Product::factory()->create(['price_cents' => 2050]);
        $this->get(route('products.show', $a))->assertOk()->assertSee('Adicionar ao carrinho');
        $this->put(route('cart.update', $a->id), ['quantity' => 3])->assertRedirect(route('cart.index'));
        $this->put(route('cart.update', $b->id), ['quantity' => 2])->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('R$ 73,97');
        $this->get(route('checkout.create'))->assertOk()->assertSee('R$ 73,97');
        $payload = $this->customer() + ['checkout_key' => session('checkout_key'), 'total_cents' => 1, 'payment_status' => 'paid', 'price' => 0];
        $this->post(route('checkout.store'), $payload)->assertRedirect();
        $order = Order::with('items')->sole();
        $this->assertSame(7397, $order->total_cents);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->fulfillment_status);
        $this->assertNull($order->paid_at);
        $this->assertCount(2, $order->items);
        $this->assertSame(1099, $order->items->first()->unit_price_cents);
        $name = $a->name;
        $a->update(['name' => 'Nome alterado', 'price_cents' => 9000]);
        $this->assertSame($name, $order->items()->first()->product_name);
        $this->assertSame(1099, $order->items()->first()->unit_price_cents);
        $this->get(route('checkout.success', $order->number))->assertOk()->assertSee('aguarda confirmação');
        $this->post(route('checkout.store'), $payload)->assertRedirect(route('checkout.success', $order->number));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertGuest();
    }

    public function test_invalid_quantities_unavailable_products_and_removed_items(): void
    {
        $product = Product::factory()->create();
        foreach ([-1, 100, '1.5', 'abc'] as $quantity) {
            $this->put(route('cart.update', $product->id), compact('quantity'))->assertSessionHasErrors('quantity');
        }
        $this->put(route('cart.update', $product->id), ['quantity' => 2])->assertRedirect();
        $product->update(['is_available' => false]);
        $this->get(route('cart.index'))->assertOk()->assertSee('Produto indisponível');
        $this->get(route('checkout.create'))->assertSessionHasErrors('cart');
        $this->put(route('cart.update', $product->id), ['quantity' => 0])->assertRedirect();
        $this->assertSame([], session('cart'));
        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
        $this->put(route('cart.update', $product->id), ['quantity' => 1])->assertSessionHasErrors('cart');
    }

    public function test_checkout_revalidates_category_availability_and_prices_without_partial_orders(): void
    {
        $product = Product::factory()->create();
        $this->put(route('cart.update', $product->id), ['quantity' => 2]);
        $this->get(route('checkout.create'))->assertOk();
        $payload = $this->customer() + ['checkout_key' => session('checkout_key')];
        $product->category->update(['is_active' => false]);
        $this->post(route('checkout.store'), $payload)->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $product->category->update(['is_active' => true]);
        $product->update(['price_cents' => 5555]);
        $this->post(route('checkout.store'), $payload)->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->get(route('checkout.create'))->assertOk()->assertSee('R$ 111,10');
        $this->post(route('checkout.store'), $payload)->assertRedirect();
        $this->assertSame(11110, Order::sole()->total_cents);
    }

    public function test_checkout_validates_customer_data_and_session_ownership_and_throttles(): void
    {
        $this->post(route('checkout.store'), [])->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'checkout_key']);
        $this->post(route('checkout.store'), $this->customer() + ['checkout_key' => (string) Str::uuid()])->assertStatus(419);
        $order = $this->order();
        $this->get(route('checkout.success', $order->number))->assertNotFound();
        $this->assertDatabaseCount('orders', 1);
        for ($i = 0; $i < 9; $i++) {
            $this->post(route('checkout.store'), []);
        }
        $this->post(route('checkout.store'), [])->assertStatus(429);
    }

    public function test_orders_are_private_and_permissions_apply_to_each_action(): void
    {
        $order = $this->order(['customer_name' => '<script>alert(1)</script>']);
        $this->get(route('orders.index'))->assertRedirect('/login');
        $viewer = User::factory()->viewer()->create();
        $this->actingAs($viewer)->get(route('orders.index'))->assertForbidden();
        $this->get(route('orders.show', $order))->assertForbidden();
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'confirmed'])->assertForbidden();
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor)->get(route('orders.index'))->assertOk();
        $this->get(route('orders.show', $order))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->patch(route('orders.payment', $order), ['version' => 0, 'status' => 'paid'])->assertForbidden();
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_payment_confirmation_duplicate_and_stale_updates_are_protected_and_audited(): void
    {
        $order = $this->order();
        $admin = $this->confirmTwoFactor(User::factory()->admin()->create());
        $this->actingAs($admin)->patch(route('orders.payment', $order), ['version' => 0, 'status' => 'paid'])->assertRedirect();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pending', $order->fulfillment_status);
        $this->assertSame($admin->id, $order->paid_by);
        $paidAt = $order->paid_at;
        $this->assertNotNull($paidAt);
        // Another operator submitted the old form after the payment was recorded.
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'confirmed'])->assertSessionHasErrors('status');
        $this->patch(route('orders.payment', $order), ['version' => 0, 'status' => 'paid'])->assertSessionHasErrors('status');
        $this->patch(route('orders.payment', $order), ['version' => 1, 'status' => 'paid'])->assertSessionHasErrors('status');
        $this->assertSame(1, $order->fresh()->version);
        $this->assertSame(1, AuditLog::where('action', 'order.payment_changed')->count());
        $this->assertSame($admin->id, AuditLog::where('action', 'order.payment_changed')->sole()->user_id);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Pagamento confirmado');
        $this->patch(route('orders.payment', $order), ['version' => 1, 'status' => 'refunded'])->assertRedirect();
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertTrue($paidAt->equalTo($order->fresh()->paid_at));
        $this->patch(route('orders.payment', $order), ['version' => 2, 'status' => 'paid'])->assertSessionHasErrors('status');
    }

    public function test_cancelled_orders_cannot_be_paid_and_fulfillment_follows_valid_transitions(): void
    {
        $admin = $this->confirmTwoFactor(User::factory()->admin()->create());
        $this->actingAs($admin);
        $order = $this->order();
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'completed'])->assertSessionHasErrors('status');
        $this->patch(route('orders.update', $order), ['version' => 0, 'status' => 'cancelled'])->assertRedirect();
        $this->patch(route('orders.payment', $order), ['version' => 1, 'status' => 'paid'])->assertSessionHasErrors('status');
        $this->patch(route('orders.update', $order), ['version' => 1, 'status' => 'confirmed'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->payment_status);
        $other = $this->order();
        foreach (['confirmed', 'preparing', 'ready'] as $version => $status) {
            $this->patch(route('orders.update', $other), compact('version', 'status'))->assertRedirect();
        }
        $this->patch(route('orders.update', $other), ['version' => 3, 'status' => 'completed'])->assertSessionHasErrors('status');
        $this->patch(route('orders.payment', $other), ['version' => 3, 'status' => 'paid'])->assertRedirect();
        $this->patch(route('orders.update', $other), ['version' => 4, 'status' => 'completed'])->assertRedirect();
        $this->assertSame('completed', $other->fresh()->fulfillment_status);
    }

    public function test_order_filters_and_transaction_rollback(): void
    {
        $a = $this->order(['payment_status' => 'paid', 'fulfillment_status' => 'ready']);
        $b = $this->order();
        $this->actingAs(User::factory()->editor()->create());
        $this->get(route('orders.index', ['payment' => 'paid', 'fulfillment' => 'ready']))->assertOk()->assertSee($a->number)->assertDontSee($b->number);
        $this->get(route('orders.index', ['number' => $b->number]))->assertOk()->assertSee($b->number)->assertDontSee($a->number);
        $this->post('/logout');
        $product = Product::factory()->create();
        $this->put(route('cart.update', $product->id), ['quantity' => 1]);
        $this->get(route('checkout.create'));
        \App\Models\OrderItem::creating(fn () => throw new \RuntimeException('Simulated storage failure'));
        try {
            $this->withoutExceptionHandling()->post(route('checkout.store'), $this->customer() + ['checkout_key' => session('checkout_key')]);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated storage failure', $e->getMessage());
        } finally {
            \App\Models\OrderItem::flushEventListeners();
        }
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_pending_order_badge_appears_in_navigation_and_dashboard_and_updates(): void
    {
        $first = $this->order();
        $second = $this->order();
        $this->order(['fulfillment_status' => 'confirmed']);
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="notification-badge"', false)
            ->assertSee('2 pedidos aguardam confirmação.');

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('2 novos pedidos aguardando confirmação');

        $this->patch(route('orders.update', $first), [
            'version' => 0,
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 pedido aguarda confirmação.');

        $this->patch(route('orders.update', $second), [
            'version' => 0,
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('class="notification-badge"', false)
            ->assertSee('Atendimento e confirmação manual de pagamento.');
    }

    private function customer(): array
    {
        return ['customer_name' => 'Cliente teste', 'customer_email' => 'cliente@example.com', 'customer_phone' => '11999999999', 'notes' => 'Retirada a combinar'];
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge($this->customer(), ['number' => (string) Str::uuid(), 'checkout_key' => (string) Str::uuid(), 'total_cents' => 1850, 'fulfillment_status' => 'pending', 'payment_status' => 'pending'], $overrides));
    }
}
