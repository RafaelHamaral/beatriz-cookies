<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendStoreNotification;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\StoreNotificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreNotifications
{
    // Called within the same transaction as the new order/message.
    public static function record(Order|ContactMessage $record): void
    {
        $delivery = StoreNotificationDelivery::firstOrCreate([
            'kind' => $record instanceof Order ? 'order' : 'message',
            'record_id' => $record->id,
        ]);

        DB::afterCommit(fn () => self::enqueue($delivery));
    }

    public static function enqueue(StoreNotificationDelivery $delivery): void
    {
        if ($delivery->sent_at || $delivery->queued_at) {
            return;
        }

        try {
            SendStoreNotification::dispatch($delivery->id);
            $delivery->update(['queued_at' => now()]);
        } catch (Throwable) {
            // Keep the durable record pending; never log transport exceptions or payloads.
            Log::warning('store_notification_enqueue_failed', ['delivery_id' => $delivery->id]);
        }
    }
}
