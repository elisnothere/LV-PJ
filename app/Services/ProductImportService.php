<?php

namespace App\Services;

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductImportService
{
    public function __construct(
        private ProductImportValidator $productImportValidator,
        private ImportedProductResolver $importedProductResolver,
        private ImportedProductImageDispatcher $importedProductImageDispatcher,
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
            'images_skipped' => 0,
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
                $this->productImportValidator->validate($normalized);

                $result = DB::transaction(fn () => $this->importedProductResolver->upsert($normalized));

                $this->stockSubscriptionService->notifyIfBackInStock(
                    $result['product'],
                    $result['previous_stock'],
                    $result['new_stock'],
                );

                $imageSync = $this->importedProductImageDispatcher->dispatch(
                    $result['product'],
                    $adapter->source(),
                    $normalized->imageUrls,
                );

                $stats[$result['status']]++;
                $stats['images_dispatched'] += $imageSync['images_dispatched'];
                $stats['images_failed'] += $imageSync['images_failed'];
                $stats['images_skipped'] += $imageSync['skipped'] ? 1 : 0;
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
}
