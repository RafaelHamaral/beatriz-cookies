<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const FULFILLMENT = ['pending' => 'Aguardando confirmação', 'confirmed' => 'Confirmado', 'preparing' => 'Em preparo', 'ready' => 'Pronto', 'completed' => 'Concluído', 'cancelled' => 'Cancelado'];
    public const PAYMENT = ['pending' => 'Aguardando pagamento', 'paid' => 'Pago', 'refunded' => 'Estornado'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'total_cents' => 'integer', 'version' => 'integer', 'stock_restored_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(OrderStockMovement::class);
    }
}
