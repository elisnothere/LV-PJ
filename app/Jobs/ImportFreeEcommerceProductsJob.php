<?php

namespace App\Jobs;

use App\Services\ProductImportService;
use App\Services\ProductSources\FreeEcommerceProductsAdapter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportFreeEcommerceProductsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(ProductImportService $productImportService): void
    {
        $productImportService->import(new FreeEcommerceProductsAdapter());
    }
}
