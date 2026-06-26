<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Payments\PaymentRegistrySyncService;
use Illuminate\Database\Seeder;

class PaymentRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $summary = app(PaymentRegistrySyncService::class)->sync();

        $this->command?->info(
            sprintf(
                'Payment registry synced. Methods: %d, Providers: %d',
                $summary['methods_synced'],
                $summary['providers_synced']
            )
        );
    }
}
