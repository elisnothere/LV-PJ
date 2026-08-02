<?php

namespace App\Services\ProductSources;

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Services\ProductSources\Concerns\FetchesProductsFromApi;

class FreeEcommerceProductsAdapter implements ProductSourceAdapter
{
    use FetchesProductsFromApi;

    public function source(): string
    {
        return 'free_ecommerce';
    }

    public function fetchProducts(): iterable
    {
        return $this->fetchJsonProducts(config('services.product_import.free_ecommerce_url'));
    }

    public function normalize(array $product): NormalizedProductData
    {
        return new NormalizedProductData(
            source: $this->source(),
            externalId: (string) data_get($product, 'id'),
            title: trim((string) data_get($product, 'name', '')),
            description: data_get($product, 'description'),
            categoryName: trim((string) data_get($product, 'category', 'Sin categoria')),
            priceAmount: ((int) data_get($product, 'priceCents', 0)) / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: array_values(array_filter([
                data_get($product, 'image'),
            ], fn ($image) => is_string($image) && trim($image) !== '')),
            rawPayload: $product,
            externalUpdatedAt: null,
        );
    }
}
