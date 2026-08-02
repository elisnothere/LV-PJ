<?php

namespace App\Services\ProductSources;

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Services\ProductSources\Concerns\FetchesProductsFromApi;
use Carbon\CarbonImmutable;

class RouteMisrProductsAdapter implements ProductSourceAdapter
{
    use FetchesProductsFromApi;

    public function source(): string
    {
        return 'route_misr';
    }

    public function fetchProducts(): iterable
    {
        return $this->fetchJsonProducts(config('services.product_import.route_misr_url'), 'data');
    }

    public function normalize(array $product): NormalizedProductData
    {
        $images = collect(array_merge(
            array_filter([data_get($product, 'imageCover')]),
            is_array(data_get($product, 'images')) ? data_get($product, 'images') : []
        ))
            ->filter(fn ($image) => is_string($image) && trim($image) !== '')
            ->unique()
            ->values()
            ->all();

        $categoryName = trim((string) (
            data_get($product, 'subcategory.category.name')
            ?? data_get($product, 'category.name')
            ?? data_get($product, 'subcategory.name')
            ?? 'Sin categoria'
        ));

        $updatedAt = data_get($product, 'updatedAt')
            ?? data_get($product, 'createdAt');

        return new NormalizedProductData(
            source: $this->source(),
            externalId: (string) data_get($product, 'id'),
            title: trim((string) data_get($product, 'title', '')),
            description: data_get($product, 'description'),
            categoryName: $categoryName,
            priceAmount: (float) data_get($product, 'price', 0),
            currency: 'EGP',
            vendor: trim((string) data_get($product, 'brand.name', '')) ?: null,
            stock: max(0, (int) data_get($product, 'quantity', 0)),
            imageUrls: $images,
            rawPayload: $product,
            externalUpdatedAt: $updatedAt ? CarbonImmutable::parse((string) $updatedAt) : null,
        );
    }
}
