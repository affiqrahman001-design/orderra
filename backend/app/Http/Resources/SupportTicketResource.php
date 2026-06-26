<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->public_id,
                'category' => $this->category,
                'status' => $this->status,
                'subject' => $this->subject,
                'description' => $this->description,
                'resolution_summary' => $this->resolution_summary,

                'links' => [
                    'order' => $this->order ? [
                        'id' => $this->order->public_id,
                        'order_code' => $this->order->order_code,
                        'status' => $this->order->status,
                    ] : null,
                    'refund' => $this->refund ? [
                        'id' => $this->refund->public_id,
                        'category' => $this->refund->category,
                        'status' => $this->refund->status,
                    ] : null,
                    'payment_intent' => $this->paymentIntent ? [
                        'id' => $this->paymentIntent->public_id,
                        'status' => $this->paymentIntent->status?->value ?? $this->paymentIntent->status,
                        'amount' => round(((int) $this->paymentIntent->amount) / 100, 2),
                        'currency' => $this->paymentIntent->currency,
                    ] : null,
                    'delivery_assignment_id' => $this->delivery_assignment_id,
                ],

                'contact_snapshot' => $this->contact_snapshot ?? [],
                'meta' => $this->meta ?? [],
                'opened_at' => optional($this->opened_at)?->toIso8601String(),
                'resolved_at' => optional($this->resolved_at)?->toIso8601String(),
                'closed_at' => optional($this->closed_at)?->toIso8601String(),

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
}
