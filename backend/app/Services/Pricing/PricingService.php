<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Promos\PromoCodeService;

final class PricingService
{
    public function __construct(
        private readonly PromoCodeService $promoCodeService,
    ) {}

    public function calculateForCart(Cart $cart): array
    {
        $cart->loadMissing('lines');

        $currency = $cart->currency ?: (string) config('pricing.currency', 'USD');
        $fulfillmentType = $cart->fulfillment_type ?: 'delivery';

        $subtotal = $cart->lines->sum(
            fn (CartItem $line): int => (int) $line->line_subtotal_amount
        );

        $discount = $this->resolvePromoDiscount($cart, $subtotal);
        $discountedSubtotal = max(0, $subtotal - $discount);

        $serviceFee = $this->resolveConfiguredFee(
            fulfillmentType: $fulfillmentType,
            feeKey: 'service_fee',
            discountedSubtotal: $discountedSubtotal,
        );

        $deliveryFee = $this->resolveConfiguredFee(
            fulfillmentType: $fulfillmentType,
            feeKey: 'delivery_fee',
            discountedSubtotal: $discountedSubtotal,
        );

        $smallOrderFee = $this->resolveSmallOrderFee(
            fulfillmentType: $fulfillmentType,
            discountedSubtotal: $discountedSubtotal,
        );

        $tax = $this->resolveTax(
            subtotal: $subtotal,
            discountedSubtotal: $discountedSubtotal,
            serviceFee: $serviceFee,
            deliveryFee: $deliveryFee,
            smallOrderFee: $smallOrderFee,
        );

        $tip = $this->resolveTip($cart, $discountedSubtotal);

        $total = $this->roundAmount(
            $discountedSubtotal + $serviceFee + $deliveryFee + $smallOrderFee + $tax + $tip
        );

        return [
            'currency' => $currency,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'service_fee' => $serviceFee,
            'delivery_fee' => $deliveryFee,
            'small_order_fee' => $smallOrderFee,
            'tax' => $tax,
            'tip' => $tip,
            'total' => $total,
            'meta' => [
                'fulfillment_type' => $fulfillmentType,
                'promo_code' => $cart->promo_code,
                'promo_placeholder_enabled' => (bool) config('pricing.promo.enabled', true),
            ],
        ];
    }

    private function resolvePromoDiscount(Cart $cart, int $subtotal): int
    {
        return $this->promoCodeService->resolveDiscount($cart->promo_code, $subtotal);
    }

    private function resolveConfiguredFee(string $fulfillmentType, string $feeKey, int $discountedSubtotal): int
    {
        $rule = config("pricing.fulfillment.{$fulfillmentType}.{$feeKey}", []);

        if ($rule === []) {
            return 0;
        }

        $type = (string) ($rule['type'] ?? 'fixed');

        if ($type === 'bps') {
            $amount = $this->applyBps($discountedSubtotal, (int) ($rule['value'] ?? 0));
            $min = (int) ($rule['min_cents'] ?? 0);
            $max = (int) ($rule['max_cents'] ?? 0);

            if ($min > 0) {
                $amount = max($amount, $min);
            }

            if ($max > 0) {
                $amount = min($amount, $max);
            }

            return $amount;
        }

        return (int) ($rule['value_cents'] ?? 0);
    }

    private function resolveSmallOrderFee(string $fulfillmentType, int $discountedSubtotal): int
    {
        $rule = config("pricing.fulfillment.{$fulfillmentType}.small_order_fee", []);

        if (! ($rule['enabled'] ?? false)) {
            return 0;
        }

        $threshold = (int) ($rule['threshold_cents'] ?? 0);

        if ($discountedSubtotal >= $threshold) {
            return 0;
        }

        return (int) ($rule['fee_cents'] ?? 0);
    }

    private function resolveTax(
        int $subtotal,
        int $discountedSubtotal,
        int $serviceFee,
        int $deliveryFee,
        int $smallOrderFee,
    ): int {
        $discountReducesTaxableSubtotal = (bool) config('pricing.promo.discount_reduces_taxable_subtotal', true);
        $taxableSubtotal = $discountReducesTaxableSubtotal ? $discountedSubtotal : $subtotal;

        $tax = 0;

        foreach ((array) config('pricing.fallback_tax.rules', []) as $rule) {
            $base = 0;

            foreach ((array) ($rule['applies_to'] ?? []) as $component) {
                $base += match ($component) {
                    'subtotal' => $taxableSubtotal,
                    'service_fee' => $serviceFee,
                    'delivery_fee' => $deliveryFee,
                    'small_order_fee' => $smallOrderFee,
                    default => 0,
                };
            }

            $tax += $this->applyBps($base, (int) ($rule['rate_bps'] ?? 0));
        }

        return $this->roundAmount($tax);
    }

    private function resolveTip(Cart $cart, int $discountedSubtotal): int
    {
        if (! config('pricing.tip.enabled', true)) {
            return 0;
        }

        $allowedFulfillment = (array) config('pricing.tip.allowed_fulfillment', []);

        if (! in_array($cart->fulfillment_type, $allowedFulfillment, true)) {
            return 0;
        }

        if ($cart->tip_type === 'amount') {
            return min(
                (int) config('pricing.tip.max_amount_cents', 5000),
                max(0, (int) $cart->tip_value)
            );
        }

        if ($cart->tip_type === 'percentage') {
            return $this->applyBps($discountedSubtotal, max(0, (int) $cart->tip_value));
        }

        return 0;
    }

    private function applyBps(int $amount, int $bps): int
    {
        return (int) round(($amount * $bps) / 10000);
    }

    private function roundAmount(int $amount): int
    {
        $increment = max(1, (int) config('pricing.rounding.increment_cents', 1));

        return (int) (round($amount / $increment) * $increment);
    }
}
