<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'price',
        'promotional_price',
        'promotional_starts_at',
        'promotional_ends_at',
        'stock',
        'image_url',
        'active',
        'canonical_key',
        'primary_source',
        'vendor',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'promotional_starts_at' => 'datetime',
            'promotional_ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function sourceMappings(): HasMany
    {
        return $this->hasMany(ProductSourceMapping::class)
            ->orderBy('source')
            ->orderBy('external_id');
    }

    public function stockSubscriptions(): HasMany
    {
        return $this->hasMany(ProductStockSubscription::class)
            ->orderBy('status')
            ->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryImage')) {
            return $this->primaryImage?->image_url ?? $this->image_url;
        }

        return $this->primaryImage()->value('image_url') ?? $this->image_url;
    }

    public function hasActivePromotion(?Carbon $now = null): bool
    {
        $promotionalPrice = $this->promotional_price;

        if ($promotionalPrice === null || (float) $promotionalPrice >= (float) $this->price) {
            return false;
        }

        $now ??= now();

        if ($this->promotional_starts_at && $now->lt($this->promotional_starts_at)) {
            return false;
        }

        if ($this->promotional_ends_at && $now->gt($this->promotional_ends_at)) {
            return false;
        }

        return true;
    }

    public function effectivePrice(?Carbon $now = null): float
    {
        return $this->hasActivePromotion($now)
            ? (float) $this->promotional_price
            : (float) $this->price;
    }

    public function originalPrice(?Carbon $now = null): ?float
    {
        return $this->hasActivePromotion($now)
            ? (float) $this->price
            : null;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->effectivePrice();
    }

    public function getOriginalPriceAttribute(): ?float
    {
        return $this->originalPrice();
    }

    public static function makeCanonicalKey(string $value): string
    {
        $canonical = Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();

        return $canonical !== '' ? $canonical : 'producto-sin-nombre';
    }
}
