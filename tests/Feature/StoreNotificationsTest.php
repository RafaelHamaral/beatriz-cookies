<?php

namespace Tests\Feature;

use App\Jobs\SendStoreNotification;
use App\Mail\StoreActivityMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreNotificationDelivery;
use App\Services\StoreNotificationSender;
use App\Services\StoreNotifications;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

class StoreNotificationsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'store_notifications.mailer' => 'smtp',
            'store_notifications.recipient' => 'contato@example.com',
            'mail.from.address' => 'loja@example.com',
            'mail.from.name' => 'Site Beatriz',
            'mail.mailers.smtp.username' => 'smtp-test-user',
            'mail.mailers.smtp.password' => 'smtp-test-secret',
        ]);
    }

    private function contact(): array
    {
        return ['name' => 'Visitante fictício', 'email' => 'visitante@example.com', 'phone' => '(11) 99999-9999',
            'subject' => 'Dúvida fictícia', 'message' => "Mensagem de teste\n<script>conteúdo</script>"];
    }

    private function checkout(): array
    {
        $product = Product::factory()->create(['name' => 'Cookie de teste', 'price_cents' => 1250]);
        $this->put(route('cart.update', $product->id), ['quantity' => 2]);
        $this->get(route('checkout.create'))->assertOk();

        return ['checkout_key' => session('checkout_key'), 'customer_name' => 'Visitante fictício',
            'customer_email' => 'visitante@example.com', 'customer_phone' => '(11) 99999-9999', 'notes' => 'Sem açúcar'];
    }

    public function test_contact_is_saved_then_queued_and_delivered_once_with_safe_content(): void
    {
        Queue::fake();
        Mail::fake();
        $this->post(route('contact.store'), $this->contact())->assertSessionHasNoErrors()->assertRedirect(route('contact.create'));
        $message = ContactMessage::sole();
        $delivery = StoreNotificationDelivery::sole();
        $this->assertSame('message', $delivery->kind);
        $this->assertSame($message->id, $delivery->record_id);
        Queue::assertPushed(SendStoreNotification::class, 1);
        Mail::assertNothingSent();
        $job = new SendStoreNotification($delivery->id);
        $job->handle(app(StoreNotificationSender::class));
        $job->handle(app(StoreNotificationSender::class));
        Mail::assertSent(StoreActivityMail::class, function ($mail) {
            $mail->assertHasSubject('Nova mensagem — Site Beatriz');
            $mail->assertFrom('loja@example.com');
            $mail->assertHasReplyTo('visitante@example.com');
            $html = $mail->render();
            $this->assertStringContainsString('Mensagem de teste', $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
            $this->assertStringNotContainsString('<script>', $html);
            return $mail->hasTo('contato@example.com');
        });
        Mail::assertSentCount(1);
        $this->assertNotNull($delivery->fresh()->sent_at);
    }

    public function test_checkout_notification_contains_snapshots_and_duplicate_submission_does_not_send_again(): void
    {
        Queue::fake();
        Mail::fake();
        $payload = $this->checkout();
        $this->post(route('checkout.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('checkout.store'), $payload)->assertRedirect();
        $order = Order::sole();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('store_notification_deliveries', 1);
        Queue::assertPushed(SendStoreNotification::class, 1);
        Product::query()->update(['price_cents' => 9999, 'name' => 'Nome alterado']);
        $job = new SendStoreNotification(StoreNotificationDelivery::sole()->id);
        $job->handle(app(StoreNotificationSender::class));
        $job->handle(app(StoreNotificationSender::class));
        Mail::assertSent(StoreActivityMail::class, function ($mail) use ($order) {
            $mail->assertHasSubject("Novo pedido #{$order->id} — Site Beatriz");
            $mail->assertFrom('loja@example.com');
            $mail->assertHasReplyTo('visitante@example.com');
            $html = $mail->render();
            foreach (['Cookie de teste', 'R$ 12,50', 'R$ 25,00', 'Sem açúcar', '(11) 99999-9999'] as $text) {
                $this->assertStringContainsString($text, $html);
            }
            return $mail->hasTo('contato@example.com');
        });
        Mail::assertSentCount(1);
    }

    public function test_rollback_and_invalid_requests_do_not_queue_notifications(): void
    {
        Queue::fake();
        try {
            DB::transaction(function () {
                $message = ContactMessage::create($this->contact());
                StoreNotifications::record($message);
                Queue::assertNothingPushed();
                throw new RuntimeException('Rollback de teste');
            });
        } catch (RuntimeException) {
        }
        $this->post(route('contact.store'), [])->assertSessionHasErrors();
        $this->post(route('checkout.store'), [])->assertSessionHasErrors();
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('store_notification_deliveries', 0);
    }

    public function test_smtp_failure_preserves_both_flows_and_logs_only_internal_ids_then_retry_succeeds(): void
    {
        Log::spy();
        Mail::mailer('smtp')->setSymfonyTransport(new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('smtp-test-secret visitante@example.com PRIVATE SMTP BODY');
            }

            public function __toString(): string { return 'failing-smtp'; }
        });
        // The sync queue deliberately exercises failure within each HTTP request.
        $this->post(route('contact.store'), $this->contact())->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('checkout.store'), $this->checkout())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame(2, StoreNotificationDelivery::whereNull('sent_at')->count());
        Log::shouldHaveReceived('warning')->with('store_notification_send_failed', \Mockery::on(
            fn ($context) => array_keys($context) === ['delivery_id'] && is_int($context['delivery_id'])
        ))->twice();

        $job = new SendStoreNotification(StoreNotificationDelivery::first()->id);
        try {
            $job->handle(app(StoreNotificationSender::class));
            $this->fail('SMTP deveria falhar');
        } catch (RuntimeException $e) {
            $this->assertNull($e->getPrevious());
            $this->assertStringNotContainsString('smtp-test-secret', (string) $e);
            $this->assertStringNotContainsString('visitante@example.com', (string) $e);
            $this->assertStringNotContainsString('PRIVATE SMTP BODY', (string) $e);
        }
        Mail::fake();
        foreach (StoreNotificationDelivery::all() as $delivery) {
            (new SendStoreNotification($delivery->id))->handle(app(StoreNotificationSender::class));
        }
        Mail::assertSentCount(2);
        $this->assertSame(0, StoreNotificationDelivery::whereNull('sent_at')->count());
    }

    public function test_pending_delivery_can_be_recovered_after_enqueue_failure(): void
    {
        Queue::fake();
        $message = ContactMessage::create($this->contact());
        $delivery = StoreNotificationDelivery::create(['kind' => 'message', 'record_id' => $message->id]);
        $this->artisan('notifications:dispatch-pending')->assertSuccessful();
        $this->artisan('notifications:dispatch-pending')->assertSuccessful();
        Queue::assertPushed(SendStoreNotification::class, 1);
        $this->assertNotNull($delivery->fresh()->queued_at);
    }

    public function test_database_queue_retries_and_stores_only_a_sanitized_failure(): void
    {
        config(['queue.default' => 'database']);
        Mail::mailer('smtp')->setSymfonyTransport(new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('smtp-test-secret visitante@example.com PRIVATE SMTP BODY');
            }

            public function __toString(): string { return 'failing-smtp'; }
        });
        $this->post(route('contact.store'), $this->contact())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('jobs', 1);
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString('visitante@example.com', $payload);
        $this->assertStringNotContainsString('Mensagem de teste', $payload);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            DB::table('jobs')->update(['available_at' => time() - 1]);
            $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        $exception = DB::table('failed_jobs')->value('exception');
        foreach (['smtp-test-secret', 'visitante@example.com', 'PRIVATE SMTP BODY'] as $private) {
            $this->assertStringNotContainsString($private, $exception);
        }
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertNull(StoreNotificationDelivery::sole()->sent_at);
        Mail::fake();
        $uuid = DB::table('failed_jobs')->value('uuid');
        $this->artisan('queue:retry', ['id' => [$uuid]])->assertSuccessful();
        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();
        Mail::assertSentCount(1);
        $this->assertNotNull(StoreNotificationDelivery::sole()->sent_at);
    }

    public function test_test_command_sends_two_labelled_fictitious_emails_without_creating_real_records(): void
    {
        Mail::fake();
        $this->artisan('notifications:test-smtp')->assertSuccessful();
        Mail::assertSent(StoreActivityMail::class, fn ($mail) => $mail->envelope()->subject === '[TESTE] Novo pedido #0 — Site Beatriz');
        Mail::assertSent(StoreActivityMail::class, fn ($mail) => $mail->envelope()->subject === '[TESTE] Nova mensagem — Site Beatriz');
        Mail::assertSentCount(2);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_log_transport_and_missing_credentials_are_rejected_and_invalid_reply_to_is_omitted(): void
    {
        $mail = new StoreActivityMail(new ContactMessage(['email' => "bad\r\nBcc: other@example.com"]));
        $this->assertSame([], $mail->envelope()->replyTo);
        Mail::fake();
        config(['store_notifications.mailer' => 'log']);
        $this->artisan('notifications:test-smtp')->assertFailed();
        config(['store_notifications.mailer' => 'smtp', 'mail.mailers.smtp.password' => null, 'mail.mailers.smtp.url' => null]);
        $this->artisan('notifications:test-smtp')->assertFailed();
        Mail::assertNothingSent();
    }
}
