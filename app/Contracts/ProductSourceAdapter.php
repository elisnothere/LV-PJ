<?php

namespace App\Contracts;

use App\Data\NormalizedProductData;

interface ProductSourceAdapter
{
    public function source(): string;

    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function fetchProducts(): iterable;

    /**
     * @param  array<string, mixed>  $product
     */
    public function normalize(array $product): NormalizedProductData;
}
