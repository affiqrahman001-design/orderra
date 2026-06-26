<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Cart;
use InvalidArgumentException;

class PaymentAmountResolver
{
    public function resolve(?Cart $cart = null, int|float|string|null $fallback = null): int
    {
        if ($cart !== null) {
            $resolved = $this->resolveFromCartSnapshot($cart);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        if ($fallback !== null) {
            $resolved = $this->normalizeAmount($fallback);

            if ($resolved > 0) {
                return $resolved;
            }
        }

        throw new InvalidArgumentException('Unable to resolve a valid payment amount.');
    }

    protected function resolveFromCartSnapshot(Cart $cart): ?int
    {
        $snapshot = $cart->pricing_snapshot ?? null;

        if (! is_array($snapshot) || $snapshot === []) {
            return null;
        }

        $candidateKeys = [
            'grand_total',
            'total',
            'total_amount',
            'payable_total',
            'final_total',
            'amount',
        ];

        foreach ($candidateKeys as $key) {
            if (! array_key_exists($key, $snapshot)) {
                continue;
            }

            $value = $snapshot[$key];

            if (is_array($value) && array_key_exists('amount', $value)) {
                $value = $value['amount'];
            }

            $normalized = $this->normalizeAmount($value);

            if ($normalized > 0) {
                return $normalized;
            }
        }

        return null;
    }

    protected function normalizeAmount(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.')
              ? (int) round(((float) $value) * 100)
              : (int) $value;
        }

        return 0;
    }
}
