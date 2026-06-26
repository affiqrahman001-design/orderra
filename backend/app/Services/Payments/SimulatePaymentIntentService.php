<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentAttempt;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SimulatePaymentIntentService
{
    public function __construct(
        protected DemoPaymentGuard $guard
    ) {}

    /**
     * @return array{
     *   intent: PaymentIntent,
     *   attempt: PaymentAttempt,
     *   transaction: PaymentTransaction,
     *   result: array<string,mixed>
     * }
     */
    public function handle(PaymentIntent $intent, array $payload): array
    {
        $this->guard->assertDemoModeEnabled();

        if ($intent->isTerminal()) {
            throw ValidationException::withMessages([
                'payment_intent' => 'Payment intent sudah berada pada terminal status dan tidak boleh disimulasikan semula.',
            ]);
        }

        $provider = PaymentProvider::query()
            ->active()
            ->where('code', $this->enumValue($intent->provider_code))
            ->first();

        if (! $provider) {
            throw new InvalidArgumentException('Payment provider untuk intent ini tidak aktif atau tidak dijumpai.');
        }

        $this->guard->assertProviderIsDemoSafe($provider);

        $outcome = $this->guard->normalizeOutcome($payload['simulation_outcome'] ?? null);
        $attemptNumber = ((int) $intent->attempts()->max('attempt_number')) + 1;
        $providerReference = 'demo_'.$outcome.'_'.Str::lower(Str::random(12));

        [$intentStatus, $attemptStatus, $transactionStatus, $errorCode, $errorMessage] = $this->statusMap($outcome);

        return DB::transaction(function () use ($intent, $outcome, $attemptNumber, $providerReference, $intentStatus, $attemptStatus, $transactionStatus, $errorCode, $errorMessage): array {
            $now = now();

            $attempt = $intent->attempts()->create([
                'attempt_number' => $attemptNumber,
                'method_code' => $this->enumValue($intent->method_code),
                'provider_code' => $this->enumValue($intent->provider_code),
                'status' => $attemptStatus,
                'amount' => (int) $intent->amount,
                'simulation_outcome' => $outcome,
                'provider_reference' => $providerReference,
                'request_payload' => [
                    'simulation_outcome' => $outcome,
                    'demo_safe' => true,
                ],
                'response_payload' => [
                    'provider_reference' => $providerReference,
                    'status' => $attemptStatus,
                    'message' => $errorMessage,
                ],
                'meta' => [
                    'demo' => true,
                    'manual_simulation' => true,
                ],
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'initiated_at' => $now,
                'processed_at' => $attemptStatus === PaymentAttemptStatus::PENDING->value ? null : $now,
            ]);

            $transaction = $intent->transactions()->create([
                'payment_attempt_id' => $attempt->id,
                'transaction_type' => 'authorization',
                'direction' => 'debit',
                'status' => $transactionStatus,
                'method_code' => $this->enumValue($intent->method_code),
                'provider_code' => $this->enumValue($intent->provider_code),
                'currency' => $intent->currency,
                'amount' => (int) $intent->amount,
                'provider_reference' => $providerReference,
                'external_reference' => null,
                'payload' => [
                    'demo' => true,
                    'simulation_outcome' => $outcome,
                ],
                'occurred_at' => $transactionStatus === PaymentTransactionStatus::PENDING->value ? null : $now,
            ]);

            $intentUpdate = [
                'status' => $intentStatus,
                'last_attempted_at' => $now,
            ];

            if ($intentStatus === PaymentIntentStatus::SUCCEEDED->value) {
                $intentUpdate['authorized_at'] = $now;
                $intentUpdate['succeeded_at'] = $now;
                $intentUpdate['failed_at'] = null;
                $intentUpdate['cancelled_at'] = null;
            }

            if ($intentStatus === PaymentIntentStatus::FAILED->value) {
                $intentUpdate['failed_at'] = $now;
            }

            $intent->update($intentUpdate);

            return [
                'intent' => $intent->fresh(['attempts', 'transactions']),
                'attempt' => $attempt->fresh(),
                'transaction' => $transaction->fresh(),
                'result' => [
                    'outcome' => $outcome,
                    'payment_status' => $intentStatus,
                    'demo_safe' => true,
                    'can_place_order' => $intentStatus === PaymentIntentStatus::SUCCEEDED->value,
                    'provider_reference' => $providerReference,
                ],
            ];
        });
    }

    /**
     * @return array{0:string,1:string,2:string,3:?string,4:?string}
     */
    private function statusMap(string $outcome): array
    {
        return match ($outcome) {
            'success' => [
                PaymentIntentStatus::SUCCEEDED->value,
                PaymentAttemptStatus::SUCCEEDED->value,
                PaymentTransactionStatus::SUCCEEDED->value,
                null,
                null,
            ],
            'failed' => [
                PaymentIntentStatus::FAILED->value,
                PaymentAttemptStatus::FAILED->value,
                PaymentTransactionStatus::FAILED->value,
                'demo_payment_failed',
                'Demo payment failed by simulation.',
            ],
            'pending' => [
                PaymentIntentStatus::PENDING->value,
                PaymentAttemptStatus::PENDING->value,
                PaymentTransactionStatus::PENDING->value,
                null,
                'Demo payment is pending.',
            ],
            default => throw new InvalidArgumentException("Unsupported simulation outcome [{$outcome}]."),
        };
    }

    private function enumValue(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
