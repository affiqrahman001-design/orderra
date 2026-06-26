<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeliveryAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->public_id,
                'status' => $this->status,
                'provider_type' => $this->provider_type,
                'eta_minutes' => $this->eta_minutes,

                'order' => $this->order ? [
                    'id' => $this->order->public_id,
                    'order_code' => $this->order->order_code,
                    'status' => $this->order->status,
                    'fulfillment_type' => $this->order->fulfillment_type,
                ] : null,

                'rider' => $this->rider ? [
                    'id' => $this->rider->public_id,
                    'rider_code' => $this->rider->rider_code,
                    'name' => $this->rider->name,
                    'type' => $this->rider->type,
                    'status' => $this->rider->status,
                    'vehicle_type' => $this->rider->vehicle_type,
                ] : null,

                'context_snapshot' => $this->context_snapshot ?? [],
                'meta' => $this->meta ?? [],

                'assigned_at' => optional($this->assigned_at)?->toIso8601String(),
                'picked_up_at' => optional($this->picked_up_at)?->toIso8601String(),
                'near_customer_at' => optional($this->near_customer_at)?->toIso8601String(),
                'delivered_at' => optional($this->delivered_at)?->toIso8601String(),

                'tracking_events' => $this->trackingEvents->map(fn ($event) => [
                    'id' => $event->id,
                    'status' => $event->status,
                    'eta_minutes' => $event->eta_minutes,
                    'simulated_latitude' => $event->simulated_latitude,
                    'simulated_longitude' => $event->simulated_longitude,
                    'note' => $event->note,
                    'payload' => $event->payload ?? [],
                    'occurred_at' => optional($event->occurred_at)?->toIso8601String(),
                ])->values(),
            ],
        ];
    }
}
