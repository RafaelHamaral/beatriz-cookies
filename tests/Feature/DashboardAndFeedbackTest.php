<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAndFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_one_period_and_only_valid_paid_orders_for_financial_metrics(): void
    {
        $first = $this->order([
            'total_cents' => 5000,
            'payment_status' => 'paid',
            'fulfillment_status' => 'confirmed',
            'paid_at' => '2026-11-10 10:00:00',
        ]);
        $first->items()->createMany([
            ['product_name' => 'Cookie clássico', 'unit_price_cents' => 2000, 'quantity' => 2, 'total_cents' => 4000],
            ['product_name' => 'Brownie', 'unit_price_cents' => 1000, 'quantity' => 1, 'total_cents' => 1000],
        ]);
        $second = $this->order([
            'total_cents' => 2000,
            'payment_status' => 'paid',
            'fulfillment_status' => 'completed',
            'paid_at' => '2026-11-11 10:00:00',
        ]);
        $second->items()->create([
            'product_name' => 'Cookie clássico', 'unit_price_cents' => 400, 'quantity' => 5, 'total_cents' => 2000,
        ]);

        $this->order(['payment_status' => 'refunded', 'fulfillment_status' => 'completed', 'paid_at' => '2026-11-12 10:00:00', 'total_cents' => 90000]);
        $this->order(['payment_status' => 'paid', 'fulfillment_status' => 'cancelled', 'paid_at' => '2026-11-13 10:00:00', 'total_cents' => 80000]);
        $this->order(['payment_status' => 'paid', 'fulfillment_status' => 'completed', 'paid_at' => '2026-10-31 10:00:00', 'total_cents' => 70000]);
        $pending = $this->order();
        $pending->forceFill(['created_at' => '2026-11-15 10:00:00'])->save();
        $outsidePending = $this->order();
        $outsidePending->forceFill(['created_at' => '2026-10-15 10:00:00'])->save();

        $viewer = User::factory()->viewer()->create();
        $response = $this->actingAs($viewer)->get(route('dashboard', [
            'from' => '2026-11-01',
            'to' => '2026-11-30',
        ]));

        $response->assertOk()
            ->assertSee('R$ 70,00')
            ->assertSee('>8</strong>', false)
            ->assertSee('>1</strong>', false)
            ->assertSee('Cookie clássico')
            ->assertSee('5 un.')
            ->assertSee('R$ 50,00')
            ->assertDontSee('Categorias');

        $this->get(route('dashboard', ['from' => '2026-11-20', 'to' => '2026-11-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_contact_messages_are_private_escaped_and_track_reading_and_handling(): void
    {
        $payload = [
            'name' => '<script>Cliente</script>',
            'email' => 'cliente@example.com',
            'phone' => '11999999999',
            'subject' => '<b>Encomenda</b>',
            'message' => '<img src=x onerror=alert(1)> Preciso de ajuda.',
        ];
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact.create'));
        $message = ContactMessage::query()->sole();
        $this->assertNull($message->read_at);
        $this->assertNull($message->handled_at);

        $viewer = User::factory()->viewer()->create();
        $this->actingAs($viewer)->get(route('messages.index'))->assertForbidden();
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 mensagem não lida.');

        $this->get(route('messages.show', $message))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
        $message->refresh();
        $this->assertNotNull($message->read_at);
        $this->assertSame(1, $message->version);

        $this->patch(route('messages.update', $message), ['version' => 1, 'status' => 'handled'])
            ->assertRedirect();
        $message->refresh();
        $this->assertNotNull($message->handled_at);
        $this->assertSame($editor->id, $message->handled_by);
        $this->patch(route('messages.update', $message), ['version' => 1, 'status' => 'unread'])
            ->assertSessionHasErrors('status');
        $this->get(route('dashboard'))->assertOk()->assertSee('Caixa de contato sem novas mensagens.');
    }

    public function test_reviews_stay_hidden_until_approved_and_moderation_is_concurrency_safe(): void
    {
        $this->post(route('reviews.store'), [
            'name' => 'Cliente', 'comment' => 'Nota inválida', 'rating' => 6,
        ])->assertSessionHasErrors('rating');

        $comment = '<script>alert(1)</script> Cookie maravilhoso';
        $this->post(route('reviews.store'), [
            'name' => '<b>Beatriz</b>', 'comment' => $comment, 'rating' => 5,
        ])->assertRedirect(route('reviews.create'));
        $review = Review::query()->sole();
        $this->assertSame('pending', $review->status);
        $this->get(route('home'))->assertOk()->assertDontSee('Cookie maravilhoso');

        $viewer = User::factory()->viewer()->create();
        $this->actingAs($viewer)->get(route('reviews.index'))->assertForbidden();
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 avaliação aguarda moderação.');
        $this->get(route('reviews.index'))->assertOk()->assertSee('Cookie maravilhoso');
        $this->patch(route('reviews.update', $review), ['version' => 0, 'status' => 'approved'])
            ->assertRedirect(route('reviews.index'));

        $review->refresh();
        $this->assertSame('approved', $review->status);
        $this->assertSame($editor->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cookie maravilhoso')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        $this->patch(route('reviews.update', $review), ['version' => 0, 'status' => 'rejected'])
            ->assertSessionHasErrors('status');
        $this->assertSame('approved', $review->fresh()->status);
    }

    public function test_public_feedback_routes_are_rate_limited(): void
    {
        $contact = app('router')->getRoutes()->getByName('contact.store');
        $review = app('router')->getRoutes()->getByName('reviews.store');

        $this->assertContains('throttle:5,1', $contact->gatherMiddleware());
        $this->assertContains('throttle:5,1', $review->gatherMiddleware());
    }

    private function order(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'number' => (string) Str::uuid(),
            'checkout_key' => (string) Str::uuid(),
            'customer_name' => 'Cliente teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '11999999999',
            'total_cents' => 1850,
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ], $overrides));
    }
}
