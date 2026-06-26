<?php

declare(strict_types=1);

namespace App\Services\Promos;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Collection;

final class PromoCodeService
{
    public function activePromos(): Collection
    {
        return Promo::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('id')
            ->get();
    }

    public function validateCode(string $code, int $subtotalAmount): array
    {
        $promo = $this->findActiveCode($code);

        if ($promo === null) {
            return $this->validatePlaceholderCode($code, $subtotalAmount);
        }

        if (($promo->minimum_subtotal_amount ?? 0) > $subtotalAmount) {
            return [
                'valid' => false,
                'message' => 'Promo requires a higher basket subtotal.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Promo applied successfully.',
            'appliedPromo' => [
                'code' => $promo->code,
                'description' => $promo->description,
                'amount' => round($this->resolveDiscountAmount($promo, $subtotalAmount) / 100, 2),
            ],
        ];
    }

    public function resolveDiscount(?string $code, int $subtotalAmount): int
    {
        $normalizedCode = strtoupper(trim((string) $code));

        if ($normalizedCode === '') {
            return 0;
        }

        $promo = $this->findActiveCode($normalizedCode);

        if ($promo !== null) {
            if (($promo->minimum_subtotal_amount ?? 0) > $subtotalAmount) {
                return 0;
            }

            return $this->resolveDiscountAmount($promo, $subtotalAmount);
        }

        $placeholderCode = strtoupper((string) config('pricing.promo.placeholder_code', ''));

        if ($normalizedCode !== $placeholderCode || ! (bool) config('pricing.promo.enabled', true)) {
            return 0;
        }

        $type = (string) config('pricing.promo.placeholder_discount_type', 'percentage');

        if ($type === 'fixed') {
            return min($subtotalAmount, (int) config('pricing.promo.placeholder_value_cents', 0));
        }

        return min(
            $subtotalAmount,
            (int) round(($subtotalAmount * (int) config('pricing.promo.placeholder_value', 0)) / 10000)
        );
    }

    private function findActiveCode(string $code): ?Promo
    {
        $normalizedCode = strtoupper(trim($code));

        if ($normalizedCode === '') {
            return null;
        }

        return Promo::query()
            ->where('code', $normalizedCode)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->first();
    }

    private function resolveDiscountAmount(Promo $promo, int $subtotalAmount): int
    {
        if ($promo->discount_type === 'fixed') {
            return min($subtotalAmount, (int) $promo->fixed_amount);
        }

        return min(
            $subtotalAmount,
            (int) round(($subtotalAmount * (int) $promo->value_bps) / 10000)
        );
    }

    private function validatePlaceholderCode(string $code, int $subtotalAmount): array
    {
        $normalizedCode = strtoupper(trim($code));
        $placeholderCode = strtoupper((string) config('pricing.promo.placeholder_code', ''));

        if (! (bool) config('pricing.promo.enabled', true) || $normalizedCode === '' || $normalizedCode !== $placeholderCode) {
            return [
                'valid' => false,
                'message' => 'Promo code was not recognised.',
            ];
        }

        $discount = $this->resolveDiscount($normalizedCode, $subtotalAmount);

        return [
            'valid' => true,
            'message' => 'Promo applied successfully.',
            'appliedPromo' => [
                'code' => $placeholderCode,
                'description' => 'Demo placeholder promo applied.',
                'amount' => round($discount / 100, 2),
            ],
        ];
    }
}
