<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SimulatePaymentWebhookService
{
    public function __construct(
        protected DemoPaymentGuard $guard
    ) {}

    public function handle(PaymentIntent $intent, array $payload): PaymentWebhookEvent
    {
        $providerCode = $this->resolveProviderCode($intent, $payload['provider_code'] ?? null);

        $provider = PaymentProvider::query()
            ->active()
            ->where('code', $providerCode)
            ->first();

        if (! $provider) {
            throw new InvalidArgumentException(
                sprintf('Unable to resolve active provider [%s] for webhook simulation.', $providerCode)
            );
        }

        $this->guard->assertDemoModeEnabled();
        $this->guard->assertProviderIsDemoSafe($provider);

        $currentTime = Carbon::now();

        return PaymentWebhookEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'payment_intent_id' => $intent->id,
            'provider_code' => $providerCode,
            'event_name' => (string) $payload['event_name'],
            'delivery_status' => 'processed',
            'provider_reference' => $payload['provider_reference'] ?? null,
            'headers' => $payload['headers'] ?? [
                'x-orderra-simulated' => 'true',
                'x-orderra-provider' => $providerCode,
            ],
            'payload' => [
                'event_name' => (string) $payload['event_name'],
                'provider_code' => $providerCode,
                'payment_intent' => [
                    'public_id' => (string) $intent->public_id,
                    'intent_code' => (string) $intent->intent_code,
                    'status' => $this->enumValue($intent->status),
                    'amount' => $intent->amount,
                    'currency' => $intent->currency,
                ],
                'provider_reference' => $payload['provider_reference'] ?? null,
                'simulated_at' => $currentTime->toIso8601String(),
                'extra_payload' => $payload['payload'] ?? [],
            ],
            'received_at' => $currentTime,
            'processed_at' => $currentTime,
        ]);
    }

    protected function resolveProviderCode(PaymentIntent $intent, ?string $providerCode = null): string
    {
        if ($providerCode !== null && $providerCode !== '') {
            return $providerCode;
        }

        return $this->enumValue($intent->provider_code);
    }

    protected function enumValue(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
