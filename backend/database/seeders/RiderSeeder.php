<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Rider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class RiderSeeder extends Seeder
{
    public function run(): void
    {
        $riders = [
            [
                'rider_code' => 'SELF-RIDER-001',
                'name' => 'Marcus Lane',
                'phone' => '+1 212 555 0181',
                'type' => 'self',
                'status' => 'active',
                'vehicle_type' => 'e-bike',
                'meta' => [
                    'demo' => true,
                    'profile' => 'In-house ORDERra delivery rider',
                    'average_eta_minutes' => 24,
                ],
            ],
            [
                'rider_code' => 'SELF-RIDER-002',
                'name' => 'Noah Brooks',
                'phone' => '+1 212 555 0182',
                'type' => 'self',
                'status' => 'active',
                'vehicle_type' => 'scooter',
                'meta' => [
                    'demo' => true,
                    'profile' => 'Backup in-house rider',
                    'average_eta_minutes' => 28,
                ],
            ],
            [
                'rider_code' => 'THIRD-PARTY-DEMO-001',
                'name' => 'Third-Party Courier Placeholder',
                'phone' => null,
                'type' => 'third_party',
                'status' => 'active',
                'vehicle_type' => 'partner_network',
                'meta' => [
                    'demo' => true,
                    'profile' => 'Placeholder for future third-party dispatch provider',
                    'provider_code' => 'demo_partner_dispatch',
                ],
            ],
        ];

        foreach ($riders as $rider) {
            Rider::query()->updateOrCreate(
                ['rider_code' => $rider['rider_code']],
                [
                    'public_id' => Rider::query()
                        ->where('rider_code', $rider['rider_code'])
                        ->value('public_id') ?? (string) Str::uuid(),
                    'name' => $rider['name'],
                    'phone' => $rider['phone'],
                    'type' => $rider['type'],
                    'status' => $rider['status'],
                    'vehicle_type' => $rider['vehicle_type'],
                    'meta' => $rider['meta'],
                    'is_demo' => true,
                ],
            );
        }
    }
}
