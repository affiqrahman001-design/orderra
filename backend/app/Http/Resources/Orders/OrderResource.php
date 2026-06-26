<?php

declare(strict_types=1);

namespace App\Http\Resources\Orders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->public_id,
                'order_code' => $this->order_code,
                'status' => $this->status,
                'allowed_transitions' => $this->allowedTransitions(),
                'currency' => $this->currency,
                'fulfillment_type' => $this->fulfillment_type,
                'source' => $this->source,
                'placed_at' => optional($this->placed_at)?->toIso8601String(),
                'completed_at' => optional($this->completed_at)?->toIso8601String(),
                'cancelled_at' => optional($this->cancelled_at)?->toIso8601String(),

                'customer_context' => $this->customer_context_snapshot ?? [],
                'fulfillment_context' => $this->fulfillment_context_snapshot ?? [],

                'totals' => [
                    'subtotal' => $this->toMoney($this->subtotal_amount),
                    'discount' => $this->toMoney($this->discount_amount),
                    'service_fee' => $this->toMoney($this->service_fee_amount),
                    'delivery_fee' => $this->toMoney($this->delivery_fee_amount),
                    'small_order_fee' => $this->toMoney($this->small_order_fee_amount),
                    'tax' => $this->toMoney($this->tax_amount),
                    'tip' => $this->toMoney($this->tip_amount),
                    'total' => $this->toMoney($this->total_amount),
                ],

                'items' => $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'item_slug' => $item->item_slug,
                    'image_url' => ((array) ($item->item_snapshot ?? []))['image_url'] ?? null,
                    'quantity' => $item->quantity,
                    'note' => $item->note,
                    'item_snapshot' => $item->item_snapshot ?? [],
                    'modifier_snapshot' => $item->modifier_snapshot ?? [],
                    'unit_price' => $this->toMoney($item->unit_price_amount),
                    'line_subtotal' => $this->toMoney($item->line_subtotal_amount),
                ])->values(),

                'fulfillment' => $this->fulfillment ? [
                    'contact_name' => $this->fulfillment->contact_name,
                    'contact_phone' => $this->fulfillment->contact_phone,
                    'scheduled_for' => optional($this->fulfillment->scheduled_for)?->toIso8601String(),
                    'eta_minutes' => $this->fulfillment->eta_minutes,
                    'pickup_code' => $this->fulfillment->pickup_code,
                    'table_label' => $this->fulfillment->table_label,
                    'party_size' => $this->fulfillment->party_size,
                    'address_snapshot' => $this->fulfillment->address_snapshot ?? [],
                    'context_snapshot' => $this->fulfillment->context_snapshot ?? [],
                ] : null,

                'status_history' => $this->statusHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'reason' => $history->reason,
                    'changed_by_type' => $history->changed_by_type,
                    'changed_by_id' => $history->changed_by_id,
                    'meta' => $history->meta ?? [],
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values(),

                'refunds' => $this->whenLoaded('refunds', fn () => $this->refunds->map(fn ($refund) => [
                    'id' => $refund->public_id,
                    'category' => $refund->category,
                    'status' => $refund->status,
                    'resolution_type' => $refund->resolution_type,
                    'requested_amount' => $this->toMoney((int) $refund->requested_amount),
                    'resolved_amount' => $refund->resolved_amount !== null ? $this->toMoney((int) $refund->resolved_amount) : null,
                    'requested_at' => optional($refund->requested_at)?->toIso8601String(),
                    'resolved_at' => optional($refund->resolved_at)?->toIso8601String(),
                ])->values()),

                'support_tickets' => $this->whenLoaded('supportTickets', fn () => $this->supportTickets->map(fn ($ticket) => [
                    'id' => $ticket->public_id,
                    'category' => $ticket->category,
                    'status' => $ticket->status,
                    'subject' => $ticket->subject,
                    'opened_at' => optional($ticket->opened_at)?->toIso8601String(),
                ])->values()),

                'delivery_assignment' => $this->whenLoaded('deliveryAssignment', fn () => $this->deliveryAssignment ? [
                    'id' => $this->deliveryAssignment->public_id,
                    'status' => $this->deliveryAssignment->status,
                    'provider_type' => $this->deliveryAssignment->provider_type,
                    'eta_minutes' => $this->deliveryAssignment->eta_minutes,
                    'assigned_at' => optional($this->deliveryAssignment->assigned_at)?->toIso8601String(),
                    'picked_up_at' => optional($this->deliveryAssignment->picked_up_at)?->toIso8601String(),
                    'near_customer_at' => optional($this->deliveryAssignment->near_customer_at)?->toIso8601String(),
                    'delivered_at' => optional($this->deliveryAssignment->delivered_at)?->toIso8601String(),
                    'rider' => $this->deliveryAssignment->rider ? [
                        'id' => $this->deliveryAssignment->rider->public_id,
                        'rider_code' => $this->deliveryAssignment->rider->rider_code,
                        'name' => $this->deliveryAssignment->rider->name,
                        'type' => $this->deliveryAssignment->rider->type,
                    ] : null,
                    'tracking_events' => $this->deliveryAssignment->trackingEvents->map(fn ($event) => [
                        'id' => $event->id,
                        'status' => $event->status,
                        'eta_minutes' => $event->eta_minutes,
                        'occurred_at' => optional($event->occurred_at)?->toIso8601String(),
                    ])->values(),
                ] : null),
            ],
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
