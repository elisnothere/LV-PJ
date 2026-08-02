<?php

namespace App\Services;

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Jobs\DownloadProductImageJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSourceMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImportService
{
    public function __construct(
        private CategoryService $categoryService,
        private StockSubscriptionService $stockSubscriptionService,
    ) {
    }

    public function import(ProductSourceAdapter $adapter): array
    {
        $stats = [
            'source' => $adapter->source(),
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'images_dispatched' => 0,
            'images_failed' => 0,
        ];

        try {
            $payloads = $adapter->fetchProducts();
        } catch (Throwable $exception) {
            $stats['failed']++;

            Log::error('Product source import failed before processing items', [
                'source' => $adapter->source(),
                'message' => $exception->getMessage(),
            ]);

            return $stats;
        }

        foreach ($payloads as $index => $payload) {
            $stats['fetched']++;

            try {
                $normalized = $adapter->normalize($payload);
                $this->validateNormalizedProduct($normalized);

                $result = DB::transaction(fn () => $this->upsertProduct($normalized));

                $this->stockSubscriptionService->notifyIfBackInStock(
                    $result['product'],
                    $result['previous_stock'],
                    $result['new_stock'],
                );

                $stats[$result['status']]++;
                $stats['images_dispatched'] += $result['images_dispatched'];
                $stats['images_failed'] += $result['images_failed'];
            } catch (Throwable $exception) {
                $stats['failed']++;

                Log::warning('Product import item failed', [
                    'source' => $adapter->source(),
                    'item_index' => $index,
                    'external_id' => is_array($payload) ? data_get($payload, 'id') : null,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('Product import finished', $stats);

        return $stats;
    }

    /**
     * @return array{status: string, images_dispatched: int, images_failed: int, previous_stock: int, new_stock: int, product: Product}
     */
    private function upsertProduct(NormalizedProductData $normalized): array
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
            $product = new Product();
            $status = 'created';

            $this->fillProduct($product, $normalized, $category)->save();

            $product->sourceMappings()->create([
                'source' => $normalized->source,
                'external_id' => $normalized->externalId,
                'external_updated_at' => $normalized->externalUpdatedAt,
                'checksum' => $checksum,
                'raw_payload' => $normalized->rawPayload,
            ]);
        }

        $imageSync = $this->syncImages($product, $normalized);
        $product = $product->fresh(['category', 'primaryImage', 'images']);

        return [
            'status' => $status,
            'images_dispatched' => $imageSync['images_dispatched'],
            'images_failed' => $imageSync['images_failed'],
            'previous_stock' => $previousStock,
            'new_stock' => (int) $product->stock,
            'product' => $product,
        ];
    }

    private function fillProduct(Product $product, NormalizedProductData $normalized, Category $category): Product
    {
        $product->fill([
            'name' => $normalized->title,
            'category_id' => $category->id,
            'description' => $normalized->description,
            'price' => number_format($normalized->priceAmount, 2, '.', ''),
            'stock' => max(0, $normalized->stock),
            'active' => true,
            'canonical_key' => Product::makeCanonicalKey($normalized->title),
            'primary_source' => $normalized->source,
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
            || $product->stock !== max(0, $normalized->stock)
            || $product->primary_source !== $normalized->source;
    }

    private function validateNormalizedProduct(NormalizedProductData $normalized): void
    {
        if (trim($normalized->externalId) === '') {
            throw new \InvalidArgumentException('Normalized product is missing an external ID.');
        }

        if (trim($normalized->title) === '') {
            throw new \InvalidArgumentException('Normalized product is missing a title.');
        }

        if (trim($normalized->categoryName) === '') {
            throw new \InvalidArgumentException('Normalized product is missing a category.');
        }

        if ($normalized->priceAmount < 0) {
            throw new \InvalidArgumentException('Normalized product price cannot be negative.');
        }
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

    /**
     * @return array{images_dispatched: int, images_failed: int}
     */
    private function syncImages(Product $product, NormalizedProductData $normalized): array
    {
        $imagesDispatched = 0;
        $imagesFailed = 0;

        $imageUrls = collect($normalized->imageUrls)
            ->filter(fn ($imageUrl) => is_string($imageUrl) && trim($imageUrl) !== '')
            ->unique()
            ->values();

        $product->images()->update(['is_primary' => false]);

        foreach ($imageUrls as $index => $imageUrl) {
            $checksum = hash('sha256', $imageUrl);
            $image = ProductImage::query()->firstOrNew([
                'product_id' => $product->id,
                'external_url' => $imageUrl,
            ]);

            $image->fill([
                'source' => 'imported_local',
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
                'checksum' => $checksum,
            ]);

            if (! $image->image_url) {
                $image->image_url = '';
            }

            $image->save();

            if ($this->needsImageDownload($image)) {
                try {
                    DownloadProductImageJob::dispatch($image->id, $imageUrl);
                    $imagesDispatched++;
                } catch (Throwable $exception) {
                    $imagesFailed++;

                    Log::warning('Product image dispatch failed', [
                        'product_id' => $product->id,
                        'product_image_id' => $image->id,
                        'source' => $normalized->source,
                        'image_url' => $imageUrl,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $currentPrimary = $product->images()
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($currentPrimary && $this->hasLocalImage($currentPrimary)) {
            $product->forceFill(['image_url' => $currentPrimary->image_url])->save();
        }

        return [
            'images_dispatched' => $imagesDispatched,
            'images_failed' => $imagesFailed,
        ];
    }

    private function needsImageDownload(ProductImage $image): bool
    {
        if (! $this->hasLocalImage($image)) {
            return true;
        }

        $path = str_replace('/storage/', '', (string) $image->image_url);

        return ! Storage::disk('public')->exists($path);
    }

    private function hasLocalImage(ProductImage $image): bool
    {
        return str_starts_with((string) $image->image_url, '/storage/productos/importados/');
    }
}
