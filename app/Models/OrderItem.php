<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_image_url',
        'quantity',
        'unit_price',
        'regular_unit_price',
        'subtotal',
    ];

    protected $appends = [
        'display_image_url',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'regular_unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        return $this->product_image_url
            ?: $this->product?->primary_image_url;
    }
}
