<?php

namespace Database\Seeders;

use App\Models\ShippingCity;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        $cities = ShippingCity::query()
            ->where('active', true)
            ->orderBy('id')
            ->get();

        if ($cities->isEmpty()) {
            return;
        }

        $faker = FakerFactory::create('es_ES');
        $faker->seed(20260802);
        $password = Hash::make('password');

        DB::transaction(function () use ($cities, $faker, $password): void {
            $seededEmails = [];

            for ($index = 1; $index <= 200; $index++) {
                $email = sprintf('dev.user%03d@example.test', $index);
                $seededEmails[] = $email;

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $faker->name(),
                        'password' => $password,
                        'role' => $index % 50 === 0 ? 'admin' : 'cliente',
                        'active' => $index % 10 !== 0,
                    ],
                );

                $user->forceFill(['email_verified_at' => now()])->save();

                $addressCount = 1 + ($index % 3);
                $keptAddresses = [];

                for ($addressIndex = 1; $addressIndex <= $addressCount; $addressIndex++) {
                    $street = $faker->streetAddress();
                    $keptAddresses[] = $street;
                    $city = $cities[($index + $addressIndex - 2) % $cities->count()];

                    $user->addresses()->updateOrCreate(
                        ['primary_address' => $street],
                        [
                            'shipping_city_id' => $city->id,
                            'secondary_address' => $addressIndex % 2 === 0
                                ? sprintf('Departamento %d, piso %d', 10 + $index, 1 + ($index % 8))
                                : null,
                        ],
                    );
                }

                $user->addresses()
                    ->whereNotIn('primary_address', $keptAddresses)
                    ->delete();
            }

            User::query()
                ->where('email', 'like', 'dev.user%@example.test')
                ->whereNotIn('email', $seededEmails)
                ->delete();
        });
    }
}
