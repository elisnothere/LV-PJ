<?php

namespace App\Services;

use App\Contracts\ProductSourceAdapter;
use App\Services\ProductSources\EscuelaJsProductsAdapter;
use App\Services\ProductSources\FreeEcommerceProductsAdapter;
use App\Services\ProductSources\RouteMisrProductsAdapter;
use InvalidArgumentException;

class ProductSourceRegistry
{
    /**
     * @return array<int, string>
     */
    public function sources(): array
    {
        return [
            'free_ecommerce',
            'escuelajs',
            'route_misr',
        ];
    }

    public function resolve(string $source): ProductSourceAdapter
    {
        return match ($source) {
            'free_ecommerce' => new FreeEcommerceProductsAdapter(),
            'escuelajs' => new EscuelaJsProductsAdapter(),
            'route_misr' => new RouteMisrProductsAdapter(),
            default => throw new InvalidArgumentException('Unknown product source ['.$source.'].'),
        };
    }
}
