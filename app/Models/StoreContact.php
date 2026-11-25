<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreContact extends Model
{
    public const WEEKDAYS = [
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    protected $fillable = ['phone', 'email', 'address', 'whatsapp', 'instagram', 'facebook', 'tiktok', 'opening_hours'];

    protected function casts(): array
    {
        return ['opening_hours' => 'array'];
    }

    public static function current(): self
    {
        return static::query()->find(1) ?? new static;
    }

    public function socialLinks(): array
    {
        return array_filter([
            'Instagram' => $this->instagram,
            'Facebook' => $this->facebook,
            'TikTok' => $this->tiktok,
        ]);
    }
}
