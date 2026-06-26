<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\QrSession;
use App\Models\RestaurantTable;
use App\Services\DineIn\DineInSessionService;
use Illuminate\Database\Seeder;

final class DineInQrSessionSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(DineInSessionService::class);
        $activeStatuses = (array) config('dine_in.qr_sessions.active_statuses', []);

        $tables = RestaurantTable::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        foreach ($tables as $table) {
            $hasActive = QrSession::query()
                ->where('restaurant_table_id', $table->id)
                ->whereIn('status', $activeStatuses)
                ->exists();

            if ($hasActive) {
                continue;
            }

            $service->open([
                'table_code' => $table->code,
                'source' => 'seed',
                'party_size' => min(4, max(1, (int) $table->seat_capacity)),
            ]);
        }
    }
}
