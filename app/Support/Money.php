<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function parseToCents(string $value): ?int
    {
        $value = trim($value);

        if (! preg_match('/^\d{1,6}([.,]\d{1,2})?$/', $value)) {
            return null;
        }

        [$reais, $fraction] = array_pad(explode('.', str_replace(',', '.', $value), 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0', STR_PAD_RIGHT);

        return ((int) $reais * 100) + (int) $fraction;
    }

    public static function format(int $cents): string
    {
        $reais = intdiv($cents, 100);
        $fraction = abs($cents % 100);

        return 'R$ '.number_format($reais, 0, ',', '.').','.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
    }
}
