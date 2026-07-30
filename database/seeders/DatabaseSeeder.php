<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => 'admin',
                'active' => true,
            ],
        );

        Product::query()->firstOrCreate(
            ['name' => 'Notebook Lenovo IdeaPad'],
            [
                'category' => 'Tecnologia',
                'description' => 'Notebook para oficina, estudio y gestion diaria.',
                'price' => 650.00,
                'stock' => 8,
                'image_url' => asset('assets/img/prod-1.jpg'),
                'active' => true,
            ],
        );

        Product::query()->firstOrCreate(
            ['name' => 'Mouse inalambrico'],
            [
                'category' => 'Accesorios',
                'description' => 'Mouse compacto con conexion USB.',
                'price' => 18.50,
                'stock' => 25,
                'image_url' => asset('assets/img/prod-2.jpg'),
                'active' => true,
            ],
        );

        Product::query()->firstOrCreate(
            ['name' => 'Teclado mecanico'],
            [
                'category' => 'Accesorios',
                'description' => 'Teclado resistente para escritura y programacion.',
                'price' => 75.00,
                'stock' => 12,
                'image_url' => asset('assets/img/prod-3.jpg'),
                'active' => true,
            ],
        );

        Product::query()->firstOrCreate(
            ['name' => 'Monitor 24 pulgadas'],
            [
                'category' => 'Tecnologia',
                'description' => 'Monitor Full HD para escritorio.',
                'price' => 190.00,
                'stock' => 6,
                'image_url' => asset('assets/img/prod-4.jpg'),
                'active' => true,
            ],
        );
    }
}
