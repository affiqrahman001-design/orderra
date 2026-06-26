<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->public_id,
                'category' => $this->category,
                'status' => $this->status,
                'resolution_type' => $this->resolution_type,
                'currency' => $this->currency,

                'order' => $this->order ? [
                    'id' => $this->order->public_id,
                    'order_code' => $this->order->order_code,
                    'status' => $this->order->status,
                    'fulfillment_type' => $this->order->fulfillment_type,
                ] : null,

                'payment' => [
                    'payment_intent_id' => $this->paymentIntent?->public_id,
                    'payment_transaction_id' => $this->paymentTransaction?->id,
                ],

                'amounts' => [
                    'requested' => $this->toMoney((int) $this->requested_amount),
                    'approved' => $this->approved_amount !== null ? $this->toMoney((int) $this->approved_amount) : null,
                    'resolved' => $this->resolved_amount !== null ? $this->toMoney((int) $this->resolved_amount) : null,
                ],

                'reason' => $this->reason,
                'notes' => $this->notes,
                'policy_snapshot' => $this->policy_snapshot ?? [],
                'context_snapshot' => $this->context_snapshot ?? [],

                'requested_by_type' => $this->requested_by_type,
                'requested_by_id' => $this->requested_by_id,

                'requested_at' => optional($this->requested_at)?->toIso8601String(),
                'reviewed_at' => optional($this->reviewed_at)?->toIso8601String(),
                'resolved_at' => optional($this->resolved_at)?->toIso8601String(),

                'events' => $this->events->map(fn ($event) => [
                    'id' => $event->id,
                    'event_name' => $event->event_name,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'note' => $event->note,
                    'payload' => $event->payload ?? [],
                    'actor_type' => $event->actor_type,
                    'actor_id' => $event->actor_id,
                    'occurred_at' => optional($event->occurred_at)?->toIso8601String(),
                ])->values(),
            ],
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
