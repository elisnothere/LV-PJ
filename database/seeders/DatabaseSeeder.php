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

        $this->seedProduct([
            'name' => 'Notebook Lenovo IdeaPad',
            'category' => 'Tecnologia',
            'description' => 'Notebook para oficina, estudio y gestion diaria.',
            'price' => 650.00,
            'stock' => 8,
            'image_url' => asset('assets/img/prod-1.jpg'),
            'active' => true,
        ]);

        $this->seedProduct([
            'name' => 'Mouse inalambrico',
            'category' => 'Accesorios',
            'description' => 'Mouse compacto con conexion USB.',
            'price' => 18.50,
            'stock' => 25,
            'image_url' => asset('assets/img/prod-2.jpg'),
            'active' => true,
        ]);

        $this->seedProduct([
            'name' => 'Teclado mecanico',
            'category' => 'Accesorios',
            'description' => 'Teclado resistente para escritura y programacion.',
            'price' => 75.00,
            'stock' => 12,
            'image_url' => asset('assets/img/prod-3.jpg'),
            'active' => true,
        ]);

        $this->seedProduct([
            'name' => 'Monitor 24 pulgadas',
            'category' => 'Tecnologia',
            'description' => 'Monitor Full HD para escritorio.',
            'price' => 190.00,
            'stock' => 6,
            'image_url' => asset('assets/img/prod-4.jpg'),
            'active' => true,
        ]);
    }

    private function seedProduct(array $data): void
    {
        $product = Product::query()->firstOrCreate(
            ['name' => $data['name']],
            $data,
        );

        if ($product->image_url) {
            $product->images()->firstOrCreate(
                ['image_url' => $product->image_url],
                [
                    'source' => str_starts_with($product->image_url, '/storage/productos/') ? 'upload' : 'url',
                    'is_primary' => true,
                    'sort_order' => 1,
                ],
            );
        }
    }
}
