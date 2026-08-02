<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCity extends Model
{
    protected $fillable = [
        'name',
        'shipping_cost',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->orderByDesc('created_at');
    }
}
