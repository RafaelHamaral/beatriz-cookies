<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\StoreContact;
use App\Support\Money;

class OrderWhatsApp
{
    public function destination(): ?string
    {
        return StoreContact::current()->whatsappNumber();
    }

    public function url(Order $order): ?string
    {
        $number = $this->destination();
        if ($number === null || ! $order->exists) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($this->message($order));
    }

    public function message(Order $order): string
    {
        $lines = [
            'Olá! Gostaria de confirmar meu pedido no Site Beatriz.',
            'Pedido: #'.$order->number,
            'Cliente: '.$this->singleLine($order->customer_name),
            '',
            'Itens:',
        ];

        foreach ($order->items as $item) {
            $lines[] = '- '.$item->quantity.' × '.$this->singleLine($item->product_name)
                .' — '.Money::format($item->unit_price_cents).' por unidade'
                .'; subtotal '.Money::format($item->total_cents);
        }

        $lines[] = '';
        $lines[] = 'Total dos produtos: '.Money::format($order->total_cents);
        if (filled($order->notes)) {
            $lines[] = '';
            $lines[] = 'Observações:';
            $lines[] = str_replace(["\r\n", "\r"], "\n", $order->notes);
        }

        return implode("\n", $lines);
    }

    private function singleLine(string $value): string
    {
        return preg_replace('/\R/u', ' ', $value);
    }
}
