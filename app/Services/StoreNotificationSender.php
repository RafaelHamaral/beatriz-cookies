<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\StoreActivityMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OutlookMailSetting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;

class StoreNotificationSender
{
    public function send(Order|ContactMessage $record, bool $test = false): void
    {
        $settings = OutlookMailSetting::find(1);
        if ($settings) {
            $token = app(OutlookOAuth::class)->accessToken($settings->fingerprint());
            // Build afresh so persistent queue workers immediately use changes from the panel.
            $outlook = Mail::build([
                'transport' => 'smtp', 'host' => 'smtp-mail.outlook.com', 'port' => 587,
                'scheme' => 'smtp', 'require_tls' => true, 'timeout' => 20,
                'username' => $settings->from_address, 'password' => $token,
            ]);
            $outlook->getSymfonyTransport()->setAuthenticators([new XOAuth2Authenticator]);
            $outlook->to($settings->recipient)->send(new StoreActivityMail(
                $record, $test, $settings->from_address, $settings->from_name,
            ));

            return;
        }

        $mailer = config('store_notifications.mailer');
        $smtp = config("mail.mailers.$mailer", []);
        // Never use log/array/failover transports with visitors' personal data.
        if (($smtp['transport'] ?? null) !== 'smtp'
            || empty($smtp['host'])
            || (empty($smtp['url']) && (empty($smtp['username']) || empty($smtp['password'])))
            || ! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            || ! filter_var(config('store_notifications.recipient'), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Configuração SMTP das notificações incompleta.');
        }

        Mail::mailer($mailer)->to(config('store_notifications.recipient'))->send(new StoreActivityMail($record, $test));
    }
}
