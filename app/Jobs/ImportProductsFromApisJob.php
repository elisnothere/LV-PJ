<?php

namespace App\Jobs;

use App\Services\ProductSourceRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportProductsFromApisJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(ProductSourceRegistry $productSourceRegistry): void
    {
        foreach ($productSourceRegistry->sources() as $source) {
            ImportProductSourceJob::dispatch($source);
        }
    }
}
