<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSourceMapping extends Model
{
    protected $fillable = [
        'product_id',
        'source',
        'external_id',
        'external_updated_at',
        'checksum',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'external_updated_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
