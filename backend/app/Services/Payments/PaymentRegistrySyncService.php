<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentMethod;
use App\Models\PaymentProvider;

class PaymentRegistrySyncService
{
    public function sync(): array
    {
        $methodsSynced = $this->syncMethods();
        $providersSynced = $this->syncProviders();

        return [
            'methods_synced' => $methodsSynced,
            'providers_synced' => $providersSynced,
        ];
    }

    protected function syncMethods(): int
    {
        $definitions = config('payments.methods', []);
        $count = 0;

        foreach ($definitions as $code => $definition) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => (string) ($definition['label'] ?? ucfirst(str_replace('_', ' ', $code))),
                    'family' => (string) ($definition['family'] ?? 'other'),
                    'kind' => (string) ($definition['kind'] ?? 'digital'),
                    'is_active' => true,
                    'is_demo_enabled' => (bool) ($definition['demo_enabled'] ?? true),
                    'requires_intent' => (bool) ($definition['requires_intent'] ?? true),
                    'supports_manual_simulation' => (bool) ($definition['supports_manual_simulation'] ?? true),
                    'sort_order' => $count + 1,
                    'meta' => [
                        'source' => 'config',
                        'countries' => array_values($definition['countries'] ?? []),
                        'provider_codes' => array_values($definition['provider_codes'] ?? []),
                    ],
                ]
            );

            $count++;
        }

        return $count;
    }

    protected function syncProviders(): int
    {
        $definitions = config('payments.providers', []);
        $count = 0;

        foreach ($definitions as $code => $definition) {
            PaymentProvider::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => (string) ($definition['label'] ?? ucfirst(str_replace('_', ' ', $code))),
                    'driver' => (string) ($definition['driver'] ?? 'demo'),
                    'mode' => (string) ($definition['mode'] ?? 'demo'),
                    'is_active' => true,
                    'live_enabled' => (bool) ($definition['live_enabled'] ?? false),
                    'webhook_enabled' => false,
                    'supports_refunds' => false,
                    'settings' => [
                        'source' => 'config',
                        'supported_methods' => array_values($definition['supported_methods'] ?? []),
                    ],
                    'meta' => [
                        'source' => 'config',
                    ],
                ]
            );

            $count++;
        }

        return $count;
    }
}
