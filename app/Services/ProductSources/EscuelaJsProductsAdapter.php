<?php

namespace App\Services\ProductSources;

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Services\ProductSources\Concerns\FetchesProductsFromApi;
use Carbon\CarbonImmutable;

class EscuelaJsProductsAdapter implements ProductSourceAdapter
{
    use FetchesProductsFromApi;

    public function source(): string
    {
        return 'escuelajs';
    }

    public function fetchProducts(): iterable
    {
        return $this->fetchJsonProducts(config('services.product_import.escuelajs_url'));
    }

    public function normalize(array $product): NormalizedProductData
    {
        $images = collect(data_get($product, 'images', []))
            ->filter(fn ($image) => is_string($image) && trim($image) !== '')
            ->values()
            ->all();

        $updatedAt = data_get($product, 'updatedAt')
            ?? data_get($product, 'creationAt');

        return new NormalizedProductData(
            source: $this->source(),
            externalId: (string) data_get($product, 'id'),
            title: trim((string) data_get($product, 'title', '')),
            description: data_get($product, 'description'),
            categoryName: trim((string) data_get($product, 'category.name', 'Sin categoria')),
            priceAmount: (float) data_get($product, 'price', 0),
            currency: 'USD',
            vendor: trim((string) data_get($product, 'category.slug', '')) ?: null,
            stock: 0,
            imageUrls: $images,
            rawPayload: $product,
            externalUpdatedAt: $updatedAt ? CarbonImmutable::parse((string) $updatedAt) : null,
        );
    }
}
