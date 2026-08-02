<?php

namespace App\Jobs;

use App\Services\ProductImportService;
use App\Services\ProductSourceRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportProductSourceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $source)
    {
    }

    public function handle(ProductImportService $productImportService, ProductSourceRegistry $productSourceRegistry): void
    {
        $productImportService->import($productSourceRegistry->resolve($this->source));
    }
}
