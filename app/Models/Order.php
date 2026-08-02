<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];

    protected $fillable = [
        'code',
        'user_id',
        'shipping_city_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'shipping_city_name',
        'status',
        'subtotal',
        'shipping_cost',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function shippingCity(): BelongsTo
    {
        return $this->belongsTo(ShippingCity::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
