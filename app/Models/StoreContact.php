<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreContact extends Model
{
    protected $fillable = ['phone', 'email', 'address', 'whatsapp', 'instagram', 'facebook', 'tiktok'];

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
