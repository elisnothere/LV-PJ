<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SyncImportedProductImagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  array<int, string>  $imageUrls
     */
    public function __construct(
        public int $productId,
        public string $source,
        public array $imageUrls,
    ) {
    }

    public function handle(): void
    {
        $product = Product::query()->with('images')->find($this->productId);

        if (! $product) {
            return;
        }

        if ($product->images->contains(fn (ProductImage $image) => $image->source !== 'imported_local')) {
            return;
        }

        $keptImageIds = [];

        foreach ($this->normalizedImageUrls() as $index => $imageUrl) {
            $image = $this->syncSingleImage($product, $imageUrl, $index + 1);

            if ($image) {
                $keptImageIds[] = $image->id;
            }
        }

        ProductImage::query()
            ->where('product_id', $product->id)
            ->where('source', 'imported_local')
            ->when($keptImageIds !== [], fn ($query) => $query->whereNotIn('id', $keptImageIds))
            ->get()
            ->each(function (ProductImage $image) {
                $this->deleteLocalFile($image->image_url);
                $image->delete();
            });

        $this->refreshPrimaryImage($product->fresh('images'));
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Imported product image sync failed', [
            'product_id' => $this->productId,
            'source' => $this->source,
            'message' => $exception->getMessage(),
        ]);
    }

    private function syncSingleImage(Product $product, string $imageUrl, int $sortOrder): ?ProductImage
    {
        $existing = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('source', 'imported_local')
            ->where('external_url', $imageUrl)
            ->first();

        if ($existing && $this->hasStoredFile($existing->image_url)) {
            $existing->forceFill([
                'sort_order' => $sortOrder,
            ])->save();

            return $existing;
        }

        try {
            $response = $this->downloadWithRetry($imageUrl);
            $contents = $response->body();
            $this->assertValidImage($response, $contents);
            $path = $this->buildStoragePath($product->id, $imageUrl, $response, $contents);
            Storage::disk('public')->put($path, $contents);
            $publicUrl = Storage::url($path);

            $image = ProductImage::query()->updateOrCreate([
                'product_id' => $product->id,
                'source' => 'imported_local',
                'external_url' => $imageUrl,
            ], [
                'image_url' => $publicUrl,
                'sort_order' => $sortOrder,
                'checksum' => hash('sha256', $contents),
                'is_primary' => false,
            ]);

            return $image;
        } catch (Throwable $exception) {
            Log::warning('Imported product image skipped', [
                'product_id' => $product->id,
                'source' => $this->source,
                'image_url' => $imageUrl,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function refreshPrimaryImage(Product $product): void
    {
        $images = $product->images()
            ->where('source', 'imported_local')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($images->isEmpty()) {
            $product->forceFill(['image_url' => null])->save();

            return;
        }

        $primaryImage = $images->first();

        ProductImage::query()
            ->where('product_id', $product->id)
            ->where('source', 'imported_local')
            ->update(['is_primary' => false]);

        $primaryImage->forceFill(['is_primary' => true])->save();
        $product->forceFill(['image_url' => $primaryImage->image_url])->save();
    }

    private function assertValidImage(Response $response, string $contents): void
    {
        $contentType = strtolower((string) $response->header('Content-Type'));

        if ($contentType !== '' && ! str_starts_with($contentType, 'image/')) {
            throw new RuntimeException('Downloaded file is not an image.');
        }

        $maxBytes = (int) config('services.product_import.image_max_bytes', 5 * 1024 * 1024);

        if (strlen($contents) > $maxBytes) {
            throw new RuntimeException('Downloaded image exceeds the configured size limit.');
        }

        if (@getimagesizefromstring($contents) === false) {
            throw new RuntimeException('Downloaded image contents are invalid.');
        }
    }

    private function downloadWithRetry(string $imageUrl): Response
    {
        $maxAttempts = (int) config('services.product_import.image_download_attempts', 3);
        $timeout = (int) config('services.product_import.image_timeout_seconds', 60);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout($timeout)->get($imageUrl);
            } catch (Throwable $exception) {
                $lastException = $exception;

                if ($attempt === $maxAttempts) {
                    throw $exception;
                }

                $this->sleepBeforeRetry(null, $attempt);

                continue;
            }

            if ($response->successful()) {
                return $response;
            }

            if (($response->status() === 429 || $response->serverError()) && $attempt < $maxAttempts) {
                $this->sleepBeforeRetry($response, $attempt);

                continue;
            }

            throw new RequestException($response);
        }

        throw $lastException ?? new RuntimeException('Image download retry loop exited unexpectedly.');
    }

    private function sleepBeforeRetry(?Response $response, int $attempt): void
    {
        $retryAfterHeader = $response?->header('Retry-After');
        $retryAfterSeconds = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;
        $fallbackMilliseconds = ((int) config('services.product_import.retry_base_sleep_ms', 250)) * (2 ** max(0, $attempt - 1));
        $sleepMilliseconds = $retryAfterSeconds !== null ? max(0, $retryAfterSeconds * 1000) : $fallbackMilliseconds;

        usleep($sleepMilliseconds * 1000);
    }

    private function buildStoragePath(int $productId, string $imageUrl, Response $response, string $contents): string
    {
        $extension = $this->detectExtension($imageUrl, $response, $contents);

        return 'productos/importados/'.$productId.'/'.hash('sha256', $imageUrl).'.'.$extension;
    }

    private function detectExtension(string $imageUrl, Response $response, string $contents): string
    {
        $contentType = strtolower((string) $response->header('Content-Type'));

        return match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'bmp') => 'bmp',
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            default => $this->extensionFromPathOrContents($imageUrl, $contents),
        };
    }

    private function extensionFromPathOrContents(string $imageUrl, string $contents): string
    {
        $pathExtension = strtolower((string) pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        if (in_array($pathExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
            return $pathExtension === 'jpeg' ? 'jpg' : $pathExtension;
        }

        $imageInfo = @getimagesizefromstring($contents);
        $mime = strtolower((string) ($imageInfo['mime'] ?? ''));

        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            default => 'jpg',
        };
    }

    private function hasStoredFile(?string $publicUrl): bool
    {
        if (! is_string($publicUrl) || ! str_starts_with($publicUrl, '/storage/')) {
            return false;
        }

        return Storage::disk('public')->exists(str_replace('/storage/', '', $publicUrl));
    }

    private function deleteLocalFile(?string $publicUrl): void
    {
        if (! $this->hasStoredFile($publicUrl)) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $publicUrl));
    }

    /**
     * @return array<int, string>
     */
    private function normalizedImageUrls(): array
    {
        return collect($this->imageUrls)
            ->filter(fn ($imageUrl) => is_string($imageUrl) && trim($imageUrl) !== '')
            ->map(fn ($imageUrl) => trim($imageUrl))
            ->unique()
            ->values()
            ->all();
    }
}
