<?php

namespace App\Models;

use App\Observers\OrderObserver;
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
        'user_address_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'delivery_address_line_1',
        'delivery_address_line_2',
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

    protected static function booted(): void
    {
        static::observe(OrderObserver::class);
    }

    public function shippingCity(): BelongsTo
    {
        return $this->belongsTo(ShippingCity::class);
    }

    public function userAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
    }
}
