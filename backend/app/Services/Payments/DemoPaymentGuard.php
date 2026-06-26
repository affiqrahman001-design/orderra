<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Exceptions\Payments\LivePaymentExecutionBlockedException;
use App\Models\PaymentProvider;
use InvalidArgumentException;

final class DemoPaymentGuard
{
    public function assertDemoModeEnabled(): void
    {
        if (! (bool) config('payments.demo_mode', true)) {
            throw LivePaymentExecutionBlockedException::becauseDemoModeIsDisabled();
        }
    }

    public function assertWebhookSimulationAllowed(): void
    {
        $this->assertDemoModeEnabled();

        if (! (bool) config('payments.allow_webhook_simulation', true)) {
            throw LivePaymentExecutionBlockedException::becauseWebhookSimulationIsDisabled();
        }
    }

    public function assertProviderIsDemoSafe(PaymentProvider $provider): void
    {
        $providerCode = $this->scalar($provider->code);
        $driver = strtolower($this->scalar($provider->driver));
        $mode = strtolower($this->scalar($provider->mode));

        if ((bool) config('payments.block_live_execution', true) && (bool) $provider->live_enabled) {
            throw LivePaymentExecutionBlockedException::becauseDemoGuardIsActive($providerCode);
        }

        $safeModes = array_map(
            static fn (string $value): string => strtolower(trim($value)),
            (array) config('payments.safe_provider_modes', ['demo', 'sandbox', 'test'])
        );

        if (! in_array($mode, $safeModes, true)) {
            throw LivePaymentExecutionBlockedException::becauseProviderIsNotInDemoMode($providerCode);
        }

        $demoDrivers = array_map(
            static fn (string $value): string => strtolower(trim($value)),
            (array) config('payments.demo_provider_drivers', ['demo'])
        );

        if (! in_array($driver, $demoDrivers, true)) {
            throw LivePaymentExecutionBlockedException::becauseRealProviderDriverDetected($providerCode, $driver);
        }
    }

    public function normalizeOutcome(?string $outcome): string
    {
        $candidate = strtolower(trim((string) ($outcome ?: config('payments.simulation.default_outcome', 'success'))));
        $allowed = $this->allowedOutcomes();

        if (! in_array($candidate, $allowed, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported simulation outcome [%s]. Allowed: %s',
                    $candidate,
                    implode(', ', $allowed)
                )
            );
        }

        return $candidate;
    }

    /**
     * @return array<int,string>
     */
    public function allowedOutcomes(): array
    {
        return array_values(config('payments.simulation.allowed_outcomes', ['success', 'failed', 'pending']));
    }

    private function scalar(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
