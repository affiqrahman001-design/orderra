<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentIntentStatus;
use App\Models\Cart;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreatePaymentIntentService
{
    public function __construct(
        protected PaymentAmountResolver $amountResolver,
        protected DemoPaymentGuard $guard,
    ) {}

    public function handle(array $payload): PaymentIntent
    {
        $this->guard->assertDemoModeEnabled();

        $methodCode = strtolower((string) ($payload['method_code'] ?? ''));
        $countryCode = strtoupper((string) ($payload['country_code'] ?? config('payments.default_country', 'US')));
        $currency = strtoupper((string) ($payload['currency'] ?? config('payments.default_currency', 'USD')));

        if ($methodCode === '') {
            throw new InvalidArgumentException('method_code is required.');
        }

        $method = PaymentMethod::query()
            ->active()
            ->demoEnabled()
            ->where('code', $methodCode)
            ->first();

        if (! $method) {
            throw new InvalidArgumentException(sprintf('Unsupported or inactive payment method [%s].', $methodCode));
        }

        $this->assertCountryCapability($methodCode, $countryCode);

        $cart = $this->resolveCart($payload);
        $provider = $this->resolveProvider($method, $payload['provider_code'] ?? null);

        $this->guard->assertProviderIsDemoSafe($provider);

        if ($cart !== null) {
            $cart->loadMissing('lines');

            if ($cart->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart kosong dan tidak boleh dibuat payment intent.',
                ]);
            }

            if (strtoupper((string) $cart->currency) !== $currency) {
                throw ValidationException::withMessages([
                    'currency' => 'Currency payment intent mesti sama dengan currency cart.',
                ]);
            }
        }

        /*
         * Security rule:
         * - Cart flow: amount wajib datang daripada cart.pricing_snapshot.
         * - Manual demo flow sahaja boleh guna amount dari request.
         */
        $amount = $cart !== null
          ? $this->amountResolver->resolve($cart, null)
          : $this->amountResolver->resolve(null, $payload['amount'] ?? null);

        return PaymentIntent::query()->create([
            'public_id' => (string) Str::uuid(),
            'intent_code' => $this->generateIntentCode(),
            'cart_id' => $cart?->id,
            'order_id' => $payload['order_id'] ?? null,
            'user_id' => $payload['user_id'] ?? $cart?->user_id,
            'method_code' => $methodCode,
            'provider_code' => $this->stringValue($provider->code),
            'status' => PaymentIntentStatus::DRAFT->value,
            'country_code' => $countryCode,
            'currency' => $currency,
            'amount' => $amount,
            'branch_code' => $payload['branch_code'] ?? null,
            'simulation_context' => [
                'requested_outcome' => $payload['simulation_outcome'] ?? config('payments.simulation.default_outcome', 'success'),
                'source' => $cart ? 'cart' : 'manual',
                'cart_token_attached' => $cart !== null,
            ],
            'provider_context' => [
                'driver' => $provider->driver,
                'mode' => $provider->mode,
                'demo_guarded' => true,
            ],
            'meta' => Arr::wrap($payload['meta'] ?? []),
            'expires_at' => now()->addMinutes((int) config('payments.simulation.pending_auto_expires_after_minutes', 30)),
        ]);
    }

    protected function resolveCart(array $payload): ?Cart
    {
        $cartId = $payload['cart_id'] ?? null;
        $cartToken = $payload['cart_token'] ?? null;
        $cartPublicId = $payload['cart_public_id'] ?? null;

        if ($cartId) {
            $cart = Cart::query()->with('lines')->find($cartId);

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart_id' => 'Cart tidak dijumpai.',
                ]);
            }

            return $cart;
        }

        if ($cartToken !== null && $cartToken !== '') {
            $cart = Cart::query()->with('lines')->where('cart_token', $cartToken)->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart_token' => 'Cart token tidak sah.',
                ]);
            }

            return $cart;
        }

        if ($cartPublicId !== null && $cartPublicId !== '') {
            $cart = Cart::query()->with('lines')->where('public_id', $cartPublicId)->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart_public_id' => 'Cart public id tidak sah.',
                ]);
            }

            return $cart;
        }

        return null;
    }

    protected function resolveProvider(PaymentMethod $method, ?string $providerCode = null): PaymentProvider
    {
        if ($providerCode !== null && $providerCode !== '') {
            $provider = PaymentProvider::query()
                ->active()
                ->where('code', $providerCode)
                ->first();

            if (! $provider) {
                throw new InvalidArgumentException(sprintf('Unsupported or inactive payment provider [%s].', $providerCode));
            }

            $this->assertProviderSupportsMethod($provider, $this->stringValue($method->code));

            return $provider;
        }

        $providerCodes = $method->meta['provider_codes'] ?? [];

        foreach ($providerCodes as $candidate) {
            $provider = PaymentProvider::query()
                ->active()
                ->where('code', $candidate)
                ->first();

            if ($provider) {
                $this->assertProviderSupportsMethod($provider, $this->stringValue($method->code));

                return $provider;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No active payment provider found for method [%s].', $this->stringValue($method->code))
        );
    }

    protected function assertCountryCapability(string $methodCode, string $countryCode): void
    {
        $allowedMethods = config("payments.country_capabilities.{$countryCode}.methods", []);

        if (! in_array($methodCode, $allowedMethods, true)) {
            throw new InvalidArgumentException(
                sprintf('Payment method [%s] is not enabled for country [%s].', $methodCode, $countryCode)
            );
        }
    }

    protected function assertProviderSupportsMethod(PaymentProvider $provider, string $methodCode): void
    {
        $supportedMethods = $provider->settings['supported_methods'] ?? [];

        if (! in_array($methodCode, $supportedMethods, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Provider [%s] does not support method [%s].',
                    $this->stringValue($provider->code),
                    $methodCode
                )
            );
        }
    }

    protected function generateIntentCode(): string
    {
        return 'pi_'.now()->format('YmdHis').'_'.Str::lower(Str::random(8));
    }

    protected function stringValue(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
