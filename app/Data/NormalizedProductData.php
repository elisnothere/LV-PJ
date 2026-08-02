<?php

namespace App\Data;

use Carbon\CarbonImmutable;

readonly class NormalizedProductData
{
    /**
     * @param  array<int, string>  $imageUrls
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $source,
        public string $externalId,
        public string $title,
        public ?string $description,
        public string $categoryName,
        public float $priceAmount,
        public string $currency,
        public ?string $vendor,
        public int $stock,
        public array $imageUrls,
        public array $rawPayload,
        public ?CarbonImmutable $externalUpdatedAt = null,
    ) {
    }
}
