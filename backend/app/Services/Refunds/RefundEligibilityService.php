<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Models\Order;
use InvalidArgumentException;

final class RefundEligibilityService
{
    public function evaluate(Order $order, string $category, ?int $requestedAmount = null): array
    {
        $stageKey = $this->resolveStageKey($order->status);
        $stage = (array) config("refunds.stages.{$stageKey}", []);

        if ($stage === []) {
            throw new InvalidArgumentException(
                sprintf('Refund stage config could not be resolved for order status [%s].', $order->status)
            );
        }

        if (! in_array($category, (array) config('refunds.categories', []), true)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported refund category [%s].', $category)
            );
        }

        if (in_array($category, $stage['disallowed_categories'] ?? [], true)) {
            return [
                'allowed' => false,
                'stage_key' => $stageKey,
                'review_mode' => $stage['review_mode'] ?? 'review',
                'reason' => sprintf(
                    'Refund category [%s] is not allowed at order status [%s].',
                    $category,
                    $order->status
                ),
            ];
        }

        if (! in_array($category, $stage['allowed_categories'] ?? [], true)) {
            return [
                'allowed' => false,
                'stage_key' => $stageKey,
                'review_mode' => $stage['review_mode'] ?? 'review',
                'reason' => sprintf(
                    'Refund category [%s] is outside the allowed policy set for [%s].',
                    $category,
                    $order->status
                ),
            ];
        }

        $maxAmount = $this->resolveAmountCeiling($order, $category);
        $finalRequestedAmount = min($requestedAmount ?? $maxAmount, $maxAmount);

        if ($finalRequestedAmount < 1) {
            return [
                'allowed' => false,
                'stage_key' => $stageKey,
                'review_mode' => $stage['review_mode'] ?? 'review',
                'reason' => 'Refund amount must be greater than zero.',
            ];
        }

        return [
            'allowed' => true,
            'stage_key' => $stageKey,
            'review_mode' => $stage['review_mode'] ?? 'review',
            'max_amount' => $maxAmount,
            'requested_amount' => $finalRequestedAmount,
            'resolution_type' => $this->resolveResolutionType($category),
            'order_transition' => $stage['order_transition'] ?? [],
            'policy_snapshot' => [
                'stage_key' => $stageKey,
                'order_status' => $order->status,
                'fulfillment_type' => $order->fulfillment_type,
                'category' => $category,
                'review_mode' => $stage['review_mode'] ?? 'review',
                'max_amount' => $maxAmount,
            ],
        ];
    }

    private function resolveStageKey(string $orderStatus): string
    {
        foreach ((array) config('refunds.stages', []) as $stageKey => $stageConfig) {
            if (in_array($orderStatus, (array) ($stageConfig['statuses'] ?? []), true)) {
                return (string) $stageKey;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No refund stage configured for order status [%s].', $orderStatus)
        );
    }

    private function resolveResolutionType(string $category): string
    {
        return match ($category) {
            'full_refund' => 'full_refund',
            'store_credit' => 'store_credit',
            default => 'partial_refund',
        };
    }

    private function resolveAmountCeiling(Order $order, string $category): int
    {
        $rule = (array) config("refunds.amount_rules.{$category}", []);
        $strategy = (string) ($rule['strategy'] ?? 'order_total');

        return match ($strategy) {
            'fees_only' => max(
                0,
                (int) $order->delivery_fee_amount + (int) $order->service_fee_amount
            ),

            'percentage_of_total' => (int) floor(
                ((int) $order->total_amount) * (((int) ($rule['max_bps'] ?? 0)) / 10000)
            ),

            default => (int) $order->total_amount,
        };
    }
}
