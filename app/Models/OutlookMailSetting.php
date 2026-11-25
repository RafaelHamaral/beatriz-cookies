<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutlookMailSetting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['client_secret', 'access_token', 'refresh_token'];

    protected $attributes = [
        'from_address' => 'rafael_hasoftware@outlook.com.br',
        'from_name' => 'Site Beatriz',
        'recipient' => 'rafael_hasoftware@outlook.com.br',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted', 'access_token' => 'encrypted',
            'refresh_token' => 'encrypted', 'token_expires_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::find(1) ?? new static;
    }

    public function fingerprint(): string
    {
        return hash('sha256', $this->client_id.'|'.$this->client_secret.'|'.$this->from_address);
    }

    public function clearTokens(): void
    {
        $this->access_token = null;
        $this->refresh_token = null;
        $this->token_expires_at = null;
    }
}
