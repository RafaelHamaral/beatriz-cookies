<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComponent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'combo_id',
        'product_id',
        'units',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'units' => 'integer',
        ];
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'combo_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
