<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StoreActivityMail extends Mailable
{
    public function __construct(
        public Order|ContactMessage $record,
        public bool $test = false,
        public ?string $senderAddress = null,
        public ?string $senderName = null,
    ) {}

    public function envelope(): Envelope
    {
        $order = $this->record instanceof Order;
        $email = $order ? $this->record->customer_email : $this->record->email;

        return new Envelope(
            from: new Address($this->senderAddress ?? config('mail.from.address'), $this->senderName ?? config('mail.from.name')),
            replyTo: filter_var($email, FILTER_VALIDATE_EMAIL) ? [new Address($email)] : [],
            subject: ($this->test ? '[TESTE] ' : '').($order
                ? "Novo pedido #{$this->record->id} — Site Beatriz"
                : 'Nova mensagem — Site Beatriz'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.store-activity');
    }
}
