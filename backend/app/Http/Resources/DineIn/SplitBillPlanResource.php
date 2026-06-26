<?php

declare(strict_types=1);

namespace App\Http\Resources\DineIn;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SplitBillPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plan = $this->resource;
        $session = $plan->qrSession;

        return [
            'data' => [
                'id' => $plan->public_id,
                'status' => $plan->status,
                'split_type' => $plan->split_type,
                'currency' => $plan->currency,
                'participant_count' => $plan->participants->count(),
                'participant_total_sum' => $this->toMoney((int) $plan->participants->sum('total_amount')),
                'is_locked' => $plan->isLocked(),
                'can_finalize' => $plan->status === 'draft',
                'finalized_at' => optional($plan->finalized_at)?->toIso8601String(),
                'session' => $session ? [
                    'id' => $session->public_id,
                    'session_code' => $session->session_code,
                    'status' => $session->status,
                    'table' => $session->restaurantTable ? [
                        'id' => $session->restaurantTable->public_id,
                        'code' => $session->restaurantTable->code,
                        'label' => $session->restaurantTable->label,
                    ] : null,
                ] : null,
                'session_totals' => $this->formatTotals((array) $plan->session_totals_snapshot),
                'participants' => $plan->participants->map(function ($participant) {
                    return [
                        'id' => $participant->public_id,
                        'display_name' => $participant->display_name,
                        'seat_label' => $participant->seat_label,
                        'participant_order' => $participant->participant_order,
                        'is_primary_payer' => $participant->is_primary_payer,
                        'status' => $participant->status,
                        'totals' => [
                            'subtotal' => $this->toMoney((int) $participant->subtotal_amount),
                            'discount' => $this->toMoney((int) $participant->discount_amount),
                            'service_fee' => $this->toMoney((int) $participant->service_fee_amount),
                            'delivery_fee' => $this->toMoney((int) $participant->delivery_fee_amount),
                            'small_order_fee' => $this->toMoney((int) $participant->small_order_fee_amount),
                            'tax' => $this->toMoney((int) $participant->tax_amount),
                            'tip' => $this->toMoney((int) $participant->tip_amount),
                            'total' => $this->toMoney((int) $participant->total_amount),
                        ],
                        'allocations' => $participant->allocations->map(function ($allocation) {
                            return [
                                'id' => $allocation->id,
                                'allocation_type' => $allocation->allocation_type,
                                'order_id' => $allocation->order?->public_id,
                                'order_code' => $allocation->order?->order_code,
                                'order_item_id' => $allocation->order_item_id,
                                'item_name' => $allocation->item_name,
                                'item_slug' => $allocation->item_slug,
                                'quantity' => $allocation->quantity,
                                'subtotal' => $this->toMoney((int) $allocation->subtotal_amount),
                                'source_snapshot' => $allocation->source_snapshot ?? [],
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ];
    }

    private function formatTotals(array $snapshot): array
    {
        return [
            'order_count' => (int) ($snapshot['order_count'] ?? 0),
            'order_codes' => $snapshot['order_codes'] ?? [],
            'subtotal' => $this->toMoney((int) ($snapshot['subtotal_amount'] ?? 0)),
            'discount' => $this->toMoney((int) ($snapshot['discount_amount'] ?? 0)),
            'service_fee' => $this->toMoney((int) ($snapshot['service_fee_amount'] ?? 0)),
            'delivery_fee' => $this->toMoney((int) ($snapshot['delivery_fee_amount'] ?? 0)),
            'small_order_fee' => $this->toMoney((int) ($snapshot['small_order_fee_amount'] ?? 0)),
            'tax' => $this->toMoney((int) ($snapshot['tax_amount'] ?? 0)),
            'tip' => $this->toMoney((int) ($snapshot['tip_amount'] ?? 0)),
            'total' => $this->toMoney((int) ($snapshot['total_amount'] ?? 0)),
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
