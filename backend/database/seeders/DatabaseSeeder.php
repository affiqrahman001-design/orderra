<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            PaymentRegistrySeeder::class,
            PricingRulesSeeder::class,
            RiderSeeder::class,
            DineInTableSeeder::class,
            DineInQrSessionSeeder::class,
            OrderraCatalogPromoSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@orderra.test'],
            [
                'name' => 'ORDERra Demo Admin',
                'orderra_role' => 'admin',
                /** Demo-only credentials for local/portfolio installs. Rotate for any shared host. */
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@orderra.test'],
            [
                'name' => 'ORDERra Demo Customer',
                'orderra_role' => 'customer',
                /** Demo-only credentials for local/portfolio installs. Rotate for any shared host. */
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@orderra.test'],
            [
                'name' => 'ORDERra Demo Staff',
                'orderra_role' => 'staff',
                /** Demo-only credentials for local/portfolio installs. Rotate for any shared host. */
                'password' => Hash::make('password'),
            ]
        );
    }
}
