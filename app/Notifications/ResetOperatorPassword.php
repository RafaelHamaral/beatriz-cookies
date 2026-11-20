<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetOperatorPassword extends Notification
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] public string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        return (new MailMessage)
            ->subject('Redefinição de senha — Beatriz & Kathy Cookies')
            ->greeting('Olá,')
            ->line('Recebemos um pedido para redefinir a senha de um operador da loja.')
            ->action('Redefinir senha', $url)
            ->line('Se você não fez esse pedido, ignore esta mensagem. A senha atual continua valendo.')
            ->salutation('Beatriz & Kathy Cookies');
    }
}
