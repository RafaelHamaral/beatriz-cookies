<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\StoreNotificationDelivery;
use App\Services\StoreNotifications;
use App\Services\StoreNotificationSender;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ContactMessage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:dispatch-pending', function () {
    StoreNotificationDelivery::whereNull('queued_at')->whereNull('sent_at')
        ->chunkById(100, function ($deliveries) {
            foreach ($deliveries as $delivery) {
                StoreNotifications::enqueue($delivery);
            }
        });
    $this->info('Notificações pendentes verificadas.');
})->purpose('Reenfileirar notificações que não chegaram à fila');

Schedule::command('notifications:dispatch-pending')->everyMinute()->withoutOverlapping();

Artisan::command('notifications:test-smtp', function (StoreNotificationSender $sender) {
    $order = (new Order)->forceFill([
        'id' => 0, 'customer_name' => 'Visitante fictício', 'customer_email' => 'visitante@example.com',
        'customer_phone' => '(11) 00000-0000', 'notes' => 'Teste de envio, sem pedido real.', 'total_cents' => 2500,
    ]);
    $order->setRelation('items', collect([(new OrderItem)->forceFill([
        'product_name' => 'Produto fictício', 'quantity' => 2, 'unit_price_cents' => 1250, 'total_cents' => 2500,
    ])]));
    $message = (new ContactMessage)->forceFill([
        'name' => 'Visitante fictício', 'email' => 'visitante@example.com', 'phone' => '(11) 00000-0000',
        'subject' => 'Teste de contato', 'message' => 'Mensagem fictícia para verificar o SMTP do Site Beatriz.',
    ]);
    $failed = false;
    foreach (['pedido' => $order, 'mensagem' => $message] as $flow => $record) {
        try {
            $sender->send($record, true);
            $this->info("Teste de $flow: envio aceito pelo provedor SMTP. Recebimento na caixa de entrada não confirmado.");
        } catch (\Throwable) {
            $failed = true;
            $this->error("Teste de $flow: envio não confirmado. Verifique a configuração SMTP e o remetente autorizado.");
        }
    }
    return $failed ? 1 : 0;
})->purpose('Enviar dois e-mails [TESTE] com dados fictícios, sem criar registros de visitantes');
