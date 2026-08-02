<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => 'admin',
                'active' => true,
            ],
        );

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(DevelopmentSeeder::class);
    }
}
