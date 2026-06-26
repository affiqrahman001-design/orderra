<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DineInTableSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $tables = [
            ['code' => 'T1', 'label' => 'Table 1', 'seat_capacity' => 2],
            ['code' => 'T2', 'label' => 'Table 2', 'seat_capacity' => 2],
            ['code' => 'T3', 'label' => 'Table 3', 'seat_capacity' => 4],
            ['code' => 'T4', 'label' => 'Table 4', 'seat_capacity' => 4],
            ['code' => 'T5', 'label' => 'Table 5', 'seat_capacity' => 6],
            ['code' => 'BAR-1', 'label' => 'Bar Seat 1', 'seat_capacity' => 1],
            ['code' => 'BAR-2', 'label' => 'Bar Seat 2', 'seat_capacity' => 1],
        ];

        foreach ($tables as $table) {
            RestaurantTable::query()->updateOrCreate(
                ['code' => $table['code']],
                [
                    'public_id' => RestaurantTable::query()
                        ->where('code', $table['code'])
                        ->value('public_id') ?? (string) Str::uuid(),
                    'branch_id' => $branch->id,
                    'label' => $table['label'],
                    'seat_capacity' => $table['seat_capacity'],
                    'status' => 'active',
                    'meta' => [
                        'demo' => true,
                        'qr_ready' => true,
                        'branch_code' => $branch->code,
                        'source' => 'seeder',
                    ],
                ],
            );
        }
    }
}
