<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductsFromApisJob;
use Illuminate\Console\Command;

class ImportProductsFromApiCommand extends Command
{
    protected $signature = 'products:import-api';

    protected $description = 'Queue the import of products from external APIs.';

    public function handle(): int
    {
        ImportProductsFromApisJob::dispatch();

        $this->info('Import queued successfully.');

        return self::SUCCESS;
    }
}
