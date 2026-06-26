<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'public_id' => Branch::query()->where('code', 'MAIN')->value('public_id') ?? (string) Str::uuid(),
                'name' => 'ORDERra Prime Burger House',
                'status' => 'active',
                'country_code' => 'US',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'phone' => '+1 212 555 0198',
                'email' => 'hello@orderra.test',
                'address_line_1' => '128 Hudson Street',
                'address_line_2' => 'Demo Suite',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10013',
                'supports_delivery' => true,
                'supports_pickup' => true,
                'supports_dine_in' => true,
                'is_default' => true,
                'meta' => [
                    'restaurant' => [
                        'brand_name' => 'ORDERra',
                        'concept' => 'Premium fast-food burger restaurant demo',
                        'positioning' => 'single-restaurant portfolio ordering platform',
                    ],
                    'demo' => true,
                    'source' => 'seeder',
                ],
            ],
        );
    }
}
