<?php

namespace App\Services;

use App\Data\NormalizedProductData;

class ProductImportValidator
{
    public function validate(NormalizedProductData $normalized): void
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
}
