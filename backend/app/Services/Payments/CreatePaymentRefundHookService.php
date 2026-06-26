<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PaymentRefundHook;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreatePaymentRefundHookService
{
    public function __construct(
        protected DemoPaymentGuard $guard
    ) {}

    public function handle(PaymentIntent $intent, array $payload): PaymentRefundHook
    {
        $this->guard->assertWebhookSimulationAllowed();

        $provider = PaymentProvider::query()
            ->active()
            ->where('code', $this->enumValue($intent->provider_code))
            ->first();

        if (! $provider) {
            throw new InvalidArgumentException('Active payment provider could not be resolved for refund hook.');
        }

        $this->guard->assertProviderIsDemoSafe($provider);

        $transaction = $this->resolveTransaction($intent, $payload['payment_transaction_id'] ?? null);
        $amount = $this->resolveAmount($intent, $payload['amount'] ?? null);

        $currentTime = Carbon::now();

        return PaymentRefundHook::query()->create([
            'public_id' => (string) Str::uuid(),
            'payment_intent_id' => $intent->id,
            'payment_transaction_id' => $transaction?->id,
            'hook_type' => (string) $payload['hook_type'],
            'status' => 'recorded',
            'amount' => $amount,
            'currency' => (string) ($payload['currency'] ?? $intent->currency),
            'reason' => $payload['reason'] ?? null,
            'payload' => $payload['payload'] ?? [
                'intent_public_id' => (string) $intent->public_id,
                'intent_code' => (string) $intent->intent_code,
                'intent_status' => $this->enumValue($intent->status),
                'simulated_refund_hook' => true,
                'demo_safe' => true,
            ],
            'notes' => $payload['notes'] ?? 'Demo-safe refund hook placeholder only. No real refund executed.',
            'requested_at' => $currentTime,
            'processed_at' => $currentTime,
        ]);
    }

    protected function resolveTransaction(PaymentIntent $intent, ?int $transactionId = null): ?PaymentTransaction
    {
        if ($transactionId === null) {
            return $intent->transactions()->latest('id')->first();
        }

        $transaction = PaymentTransaction::query()
            ->where('payment_intent_id', $intent->id)
            ->find($transactionId);

        if (! $transaction) {
            throw new InvalidArgumentException('Provided payment_transaction_id does not belong to this payment intent.');
        }

        return $transaction;
    }

    protected function resolveAmount(PaymentIntent $intent, ?int $amount = null): int
    {
        if ($amount === null) {
            return (int) $intent->amount;
        }

        if ($amount < 1) {
            throw new InvalidArgumentException('Refund hook amount must be greater than zero.');
        }

        if ($amount > (int) $intent->amount) {
            throw new InvalidArgumentException('Refund hook amount cannot exceed the payment intent amount.');
        }

        return $amount;
    }

    protected function enumValue(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
