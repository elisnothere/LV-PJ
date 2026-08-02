<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DevelopmentShippingCitySeeder::class,
            DevelopmentProductSeeder::class,
            DevelopmentUserSeeder::class,
            DevelopmentOrderSeeder::class,
            DevelopmentStockSubscriptionSeeder::class,
        ]);
    }
}
