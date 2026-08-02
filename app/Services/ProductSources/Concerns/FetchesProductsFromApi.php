<?php

namespace App\Services\ProductSources\Concerns;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

trait FetchesProductsFromApi
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchJsonProducts(string $url, ?string $dataKey = null): array
    {
        $maxAttempts = (int) config('services.product_import.max_attempts', 3);
        $timeout = (int) config('services.product_import.timeout_seconds', 30);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::acceptJson()
                    ->timeout($timeout)
                    ->get($url);
            } catch (Throwable $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }

                $this->sleepBeforeRetry(null, $attempt);

                continue;
            }

            if ($response->successful()) {
                $payload = $response->json();
                $products = $dataKey ? data_get($payload, $dataKey) : $payload;

                if (! is_array($products)) {
                    throw new RuntimeException('Product API returned an invalid payload structure.');
                }

                return array_values(array_filter($products, fn ($product) => is_array($product)));
            }

            if ($this->shouldRetryResponse($response) && $attempt < $maxAttempts) {
                $this->sleepBeforeRetry($response, $attempt);

                continue;
            }

            throw new RequestException($response);
        }

        throw new RuntimeException('Product API retry loop exited unexpectedly.');
    }

    protected function shouldRetryResponse(Response $response): bool
    {
        return $response->status() === 429 || $response->serverError();
    }

    protected function sleepBeforeRetry(?Response $response, int $attempt): void
    {
        $retryAfterHeader = $response?->header('Retry-After');
        $retryAfterSeconds = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;
        $fallbackMilliseconds = ((int) config('services.product_import.retry_base_sleep_ms', 250)) * (2 ** max(0, $attempt - 1));

        $sleepMilliseconds = $retryAfterSeconds !== null
            ? max(0, $retryAfterSeconds * 1000)
            : $fallbackMilliseconds;

        usleep($sleepMilliseconds * 1000);
    }
}
