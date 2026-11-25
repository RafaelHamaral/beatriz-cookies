<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\StoreNotificationDelivery;
use App\Services\StoreNotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendStoreNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 45;

    public function __construct(public int $deliveryId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(StoreNotificationSender $sender): void
    {
        try {
            // Serialize concurrent deliveries and skip already accepted messages.
            DB::transaction(function () use ($sender) {
                $delivery = StoreNotificationDelivery::lockForUpdate()->findOrFail($this->deliveryId);
                if ($delivery->sent_at) {
                    return;
                }
                $record = $delivery->kind === 'order'
                    ? Order::with('items')->findOrFail($delivery->record_id)
                    : ContactMessage::findOrFail($delivery->record_id);
                $sender->send($record);
                $delivery->update(['sent_at' => now()]);
            });
        } catch (Throwable) {
            Log::warning('store_notification_send_failed', ['delivery_id' => $this->deliveryId]);
            // Queue logs/failed_jobs must not receive SMTP responses, addresses or credentials.
            throw new RuntimeException('Falha no envio da notificação; referência '.$this->deliveryId.'.');
        }
    }
}
