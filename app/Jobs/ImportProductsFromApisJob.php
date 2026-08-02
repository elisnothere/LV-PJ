<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportProductsFromApisJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(): void
    {
        ImportFreeEcommerceProductsJob::dispatch();
        ImportEscuelaJsProductsJob::dispatch();
        ImportRouteMisrProductsJob::dispatch();
    }
}
