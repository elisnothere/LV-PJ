<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStockSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentStockSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            ProductStockSubscription::query()
                ->where('email', 'like', 'stock.dev.%@example.test')
                ->delete();

            Product::query()
                ->where('canonical_key', 'like', 'dev-seed-%')
                ->where('stock', 0)
                ->orderBy('id')
                ->get()
                ->each(function (Product $product, int $index): void {
                    foreach (range(1, 2) as $subscriber) {
                        $product->stockSubscriptions()->create([
                            'email' => sprintf(
                                'stock.dev.%03d.%d@example.test',
                                $index + 1,
                                $subscriber,
                            ),
                            'status' => 'pending',
                        ]);
                    }
                });

            Product::query()
                ->where('canonical_key', 'like', 'dev-seed-%')
                ->where('stock', '>', 0)
                ->orderBy('id')
                ->limit(12)
                ->get()
                ->each(function (Product $product, int $index): void {
                    $product->stockSubscriptions()->create([
                        'email' => sprintf('stock.dev.notified.%03d@example.test', $index + 1),
                        'status' => 'notified',
                        'notified_at' => now()->subDays(1 + $index),
                    ]);
                });
        });
    }
}
