<?php

namespace App\Services;

use App\Data\NormalizedProductData;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSourceMapping;

class ImportedProductResolver
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    /**
     * @return array{status: string, previous_stock: int, new_stock: int, product: Product}
     */
    public function upsert(NormalizedProductData $normalized): array
    {
        $checksum = $this->checksum($normalized);
        $category = $this->categoryService->resolveByName($normalized->categoryName);
        $mapping = ProductSourceMapping::query()
            ->with('product.category')
            ->where('source', $normalized->source)
            ->where('external_id', $normalized->externalId)
            ->first();

        $status = 'unchanged';
        $previousStock = 0;

        if ($mapping) {
            $product = $mapping->product;

            if (! $product) {
                throw new \RuntimeException('Source mapping exists without a linked product.');
            }

            $previousStock = (int) $product->stock;

            if ($mapping->checksum !== $checksum || $this->productNeedsUpdate($product, $normalized, $category)) {
                $this->fillProduct($product, $normalized, $category)->save();
                $status = 'updated';
            }

            $mapping->forceFill([
                'external_updated_at' => $normalized->externalUpdatedAt,
                'checksum' => $checksum,
                'raw_payload' => $normalized->rawPayload,
            ])->save();
        } else {
            $product = $this->findImportedDuplicate($normalized, $category) ?? new Product();
            $status = $product->exists ? 'updated' : 'created';
            $previousStock = (int) ($product->stock ?? 0);

            $this->fillProduct($product, $normalized, $category)->save();

            $product->sourceMappings()->create([
                'source' => $normalized->source,
                'external_id' => $normalized->externalId,
                'external_updated_at' => $normalized->externalUpdatedAt,
                'checksum' => $checksum,
                'raw_payload' => $normalized->rawPayload,
            ]);
        }

        $product = $product->fresh(['category', 'primaryImage', 'images']);

        return [
            'status' => $status,
            'previous_stock' => $previousStock,
            'new_stock' => (int) $product->stock,
            'product' => $product,
        ];
    }

    private function findImportedDuplicate(NormalizedProductData $normalized, Category $category): ?Product
    {
        $canonicalKey = Product::makeCanonicalKey($normalized->title);
        $formattedPrice = number_format($normalized->priceAmount, 2, '.', '');

        return Product::query()
            ->whereNotNull('primary_source')
            ->where('canonical_key', $canonicalKey)
            ->where('category_id', $category->id)
            ->where('price', $formattedPrice)
            ->when($normalized->vendor !== null, fn ($query) => $query->where('vendor', $normalized->vendor))
            ->whereHas('sourceMappings')
            ->first();
    }

    private function fillProduct(Product $product, NormalizedProductData $normalized, Category $category): Product
    {
        $product->fill([
            'name' => $normalized->title,
            'category_id' => $category->id,
            'description' => $normalized->description,
            'price' => number_format($normalized->priceAmount, 2, '.', ''),
            'promotional_price' => null,
            'promotional_starts_at' => null,
            'promotional_ends_at' => null,
            'stock' => max(0, $normalized->stock),
            'active' => true,
            'canonical_key' => Product::makeCanonicalKey($normalized->title),
            'primary_source' => $product->primary_source ?: $normalized->source,
            'vendor' => $normalized->vendor,
        ]);

        $product->setRelation('category', $category);

        return $product;
    }

    private function productNeedsUpdate(Product $product, NormalizedProductData $normalized, Category $category): bool
    {
        return $product->name !== $normalized->title
            || $product->category_id !== $category->id
            || $product->description !== $normalized->description
            || (string) $product->price !== number_format($normalized->priceAmount, 2, '.', '')
            || $product->vendor !== $normalized->vendor
            || $product->stock !== max(0, $normalized->stock);
    }

    private function checksum(NormalizedProductData $normalized): string
    {
        return hash('sha256', json_encode([
            'source' => $normalized->source,
            'external_id' => $normalized->externalId,
            'title' => $normalized->title,
            'description' => $normalized->description,
            'category' => $normalized->categoryName,
            'price' => number_format($normalized->priceAmount, 2, '.', ''),
            'currency' => $normalized->currency,
            'vendor' => $normalized->vendor,
            'stock' => $normalized->stock,
            'images' => $normalized->imageUrls,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
