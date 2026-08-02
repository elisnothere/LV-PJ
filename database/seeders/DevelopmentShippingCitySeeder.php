<?php

namespace Database\Seeders;

use App\Models\ShippingCity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentShippingCitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Asuncion', 'shipping_cost' => 15.00, 'active' => true],
            ['name' => 'San Lorenzo', 'shipping_cost' => 18.50, 'active' => true],
            ['name' => 'Luque', 'shipping_cost' => 20.00, 'active' => true],
            ['name' => 'Capiata', 'shipping_cost' => 22.00, 'active' => true],
            ['name' => 'Fernando de la Mora', 'shipping_cost' => 17.50, 'active' => true],
            ['name' => 'Lambare', 'shipping_cost' => 16.00, 'active' => true],
            ['name' => 'Mariano Roque Alonso', 'shipping_cost' => 21.00, 'active' => true],
            ['name' => 'Encarnacion', 'shipping_cost' => 35.00, 'active' => true],
            ['name' => 'Ciudad del Este', 'shipping_cost' => 38.00, 'active' => true],
            ['name' => 'Villarrica', 'shipping_cost' => 31.00, 'active' => true],
            ['name' => 'Caaguazu', 'shipping_cost' => 33.00, 'active' => false],
            ['name' => 'Pedro Juan Caballero', 'shipping_cost' => 42.00, 'active' => false],
        ];

        DB::transaction(function () use ($cities): void {
            foreach ($cities as $city) {
                ShippingCity::query()->updateOrCreate(
                    ['name' => $city['name']],
                    $city,
                );
            }
        });
    }
}
