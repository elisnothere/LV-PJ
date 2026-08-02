<?php

namespace App\Services;

use App\Jobs\SyncImportedProductImagesJob;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportedProductImageDispatcher
{
    /**
     * @param  array<int, string>  $imageUrls
     * @return array{images_dispatched: int, images_failed: int, skipped: bool}
     */
    public function dispatch(Product $product, string $source, array $imageUrls): array
    {
        $normalizedUrls = $this->normalizeUrls($imageUrls);

        if ($product->images()->where('source', '!=', 'imported_local')->exists()) {
            return [
                'images_dispatched' => 0,
                'images_failed' => 0,
                'skipped' => true,
            ];
        }

        try {
            SyncImportedProductImagesJob::dispatch($product->id, $source, $normalizedUrls->all())->afterCommit();

            return [
                'images_dispatched' => $normalizedUrls->count(),
                'images_failed' => 0,
                'skipped' => false,
            ];
        } catch (Throwable $exception) {
            Log::warning('Imported product image sync dispatch failed', [
                'product_id' => $product->id,
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);

            return [
                'images_dispatched' => 0,
                'images_failed' => max(1, $normalizedUrls->count()),
                'skipped' => false,
            ];
        }
    }

    /**
     * @param  array<int, string>  $imageUrls
     * @return Collection<int, string>
     */
    private function normalizeUrls(array $imageUrls): Collection
    {
        return collect($imageUrls)
            ->filter(fn ($imageUrl) => is_string($imageUrl) && trim($imageUrl) !== '')
            ->map(fn ($imageUrl) => trim($imageUrl))
            ->unique()
            ->values();
    }
}
