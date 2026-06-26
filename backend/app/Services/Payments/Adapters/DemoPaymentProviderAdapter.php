<?php

declare(strict_types=1);

namespace App\Services\Payments\Adapters;

use App\Contracts\Payments\PaymentProviderAdapter;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Services\Payments\DemoPaymentGuard;
use Illuminate\Support\Str;

class DemoPaymentProviderAdapter implements PaymentProviderAdapter
{
    public function __construct(
        protected DemoPaymentGuard $guard
    ) {}

    public function driver(): string
    {
        return 'demo';
    }

    public function supports(PaymentProvider $provider): bool
    {
        return $provider->driver === $this->driver();
    }

    public function simulate(PaymentIntent $intent, array $payload = []): array
    {
        $outcome = $this->guard->normalizeOutcome($payload['simulation_outcome'] ?? null);

        [$intentStatus, $attemptStatus, $transactionStatus] = $this->mapStatuses($outcome);

        $providerReference = sprintf(
            'demo_%s',
            str_replace('-', '', (string) Str::uuid())
        );

        return [
            'outcome' => $outcome,
            'intent_status' => $intentStatus,
            'attempt_status' => $attemptStatus,
            'transaction_status' => $transactionStatus,
            'provider_reference' => $providerReference,
            'response_payload' => [
                'provider' => 'demo',
                'message' => $this->messageForOutcome($outcome),
                'simulated_at' => now()->toIso8601String(),
                'public_id' => (string) $intent->public_id,
                'intent_code' => (string) $intent->intent_code,
                'amount' => (int) $intent->amount,
                'currency' => (string) $intent->currency,
            ],
        ];
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    protected function mapStatuses(string $outcome): array
    {
        return match ($outcome) {
            'success' => [
                PaymentIntentStatus::AUTHORIZED->value,
                PaymentAttemptStatus::SUCCEEDED->value,
                PaymentTransactionStatus::SUCCEEDED->value,
            ],
            'pending' => [
                PaymentIntentStatus::PENDING->value,
                PaymentAttemptStatus::PENDING->value,
                PaymentTransactionStatus::PENDING->value,
            ],
            'failed' => [
                PaymentIntentStatus::FAILED->value,
                PaymentAttemptStatus::FAILED->value,
                PaymentTransactionStatus::FAILED->value,
            ],
        };
    }

    protected function messageForOutcome(string $outcome): string
    {
        return match ($outcome) {
            'success' => 'Demo payment authorized successfully.',
            'pending' => 'Demo payment remains pending.',
            'failed' => 'Demo payment failed.',
        };
    }
}
