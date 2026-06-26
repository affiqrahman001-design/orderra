<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProviderAdapter;
use App\Models\PaymentProvider;
use App\Services\Payments\Adapters\DemoPaymentProviderAdapter;
use InvalidArgumentException;

class PaymentProviderManager
{
    public function __construct(
        protected DemoPaymentGuard $guard,
        protected DemoPaymentProviderAdapter $demoAdapter
    ) {}

    public function resolve(PaymentProvider $provider): PaymentProviderAdapter
    {
        $this->guard->assertDemoModeEnabled();
        $this->guard->assertProviderIsDemoSafe($provider);

        return match ($provider->driver) {
            'demo' => $this->demoAdapter,
            default => throw new InvalidArgumentException(
                sprintf('Unsupported payment provider driver [%s].', $provider->driver)
            ),
        };
    }
}
