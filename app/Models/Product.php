<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'price',
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
            'active' => 'boolean',
        ];
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
