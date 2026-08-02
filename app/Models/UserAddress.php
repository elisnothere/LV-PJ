<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'shipping_city_id',
        'primary_address',
        'secondary_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingCity(): BelongsTo
    {
        return $this->belongsTo(ShippingCity::class);
    }

    public function formattedAddress(): string
    {
        return collect([
            trim((string) $this->primary_address),
            trim((string) $this->secondary_address),
        ])
            ->filter()
            ->implode(', ');
    }
}
