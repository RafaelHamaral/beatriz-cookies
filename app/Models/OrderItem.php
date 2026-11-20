<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unit_price_cents' => 'integer', 'quantity' => 'integer', 'total_cents' => 'integer'];
    }
}
