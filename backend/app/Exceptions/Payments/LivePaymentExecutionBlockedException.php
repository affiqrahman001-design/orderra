<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

class LivePaymentExecutionBlockedException extends RuntimeException
{
    public static function becauseDemoGuardIsActive(string $providerCode): self
    {
        return new self(
            sprintf(
                'Live payment execution is blocked by server-side demo guard for provider [%s].',
                $providerCode
            )
        );
    }

    public static function becauseProviderIsNotInDemoMode(string $providerCode): self
    {
        return new self(
            sprintf(
                'Provider [%s] is not in demo/sandbox/test mode. ORDERra payment execution must remain demo-safe.',
                $providerCode
            )
        );
    }

    public static function becauseRealProviderDriverDetected(string $providerCode, string $driver): self
    {
        return new self(
            sprintf(
                'Real provider driver [%s] is blocked for provider [%s]. ORDERra only allows demo-safe adapters.',
                $driver,
                $providerCode
            )
        );
    }

    public static function becauseDemoModeIsDisabled(): self
    {
        return new self('Payment demo mode is disabled. Simulation cannot proceed.');
    }

    public static function becauseWebhookSimulationIsDisabled(): self
    {
        return new self('Webhook simulation is disabled by server-side demo guard.');
    }
}
