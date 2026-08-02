<?php

namespace App\Jobs;

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

class DownloadProductImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $productImageId,
        public string $imageUrl,
    ) {
    }

    public function handle(): void
    {
        $image = ProductImage::query()->with('product')->find($this->productImageId);

        if (! $image) {
            return;
        }

        $path = $this->buildStoragePath($image->product_id, $this->imageUrl);

        if (! Storage::disk('public')->exists($path)) {
            $response = $this->downloadWithRetry();
            Storage::disk('public')->put($path, $response->body());
        }

        $publicUrl = Storage::url($path);

        $image->forceFill([
            'image_url' => $publicUrl,
            'source' => 'imported_local',
            'checksum' => hash('sha256', $this->imageUrl),
        ])->save();

        if ($image->is_primary && $image->product && $image->product->image_url !== $publicUrl) {
            $image->product->forceFill(['image_url' => $publicUrl])->save();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Product image download failed', [
            'product_image_id' => $this->productImageId,
            'image_url' => $this->imageUrl,
            'message' => $exception->getMessage(),
        ]);
    }

    private function downloadWithRetry(): Response
    {
        $maxAttempts = (int) config('services.product_import.image_download_attempts', 3);
        $timeout = (int) config('services.product_import.image_timeout_seconds', 60);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout($timeout)->get($this->imageUrl);
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

        $sleepMilliseconds = $retryAfterSeconds !== null
            ? max(0, $retryAfterSeconds * 1000)
            : $fallbackMilliseconds;

        usleep($sleepMilliseconds * 1000);
    }

    private function buildStoragePath(int $productId, string $imageUrl): string
    {
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $extension = $extension !== '' ? $extension : 'jpg';

        return 'productos/importados/'.$productId.'/'.hash('sha256', $imageUrl).'.'.$extension;
    }
}
