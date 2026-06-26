<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OpsWebhookEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->public_id,
                'event_name' => $this->event_name,
                'aggregate_type' => $this->aggregate_type,
                'status' => $this->status,
                'replay_count' => (int) $this->replay_count,

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
                        'currency' => $this->paymentIntent->currency,
                        'amount' => round(((int) $this->paymentIntent->amount) / 100, 2),
                    ] : null,
                    'delivery_assignment' => $this->deliveryAssignment ? [
                        'id' => $this->deliveryAssignment->public_id,
                        'status' => $this->deliveryAssignment->status,
                        'provider_type' => $this->deliveryAssignment->provider_type,
                    ] : null,
                ],

                'payload' => $this->payload ?? [],
                'headers' => $this->headers ?? [],
                'notes' => $this->notes,
                'generated_at' => optional($this->generated_at)?->toIso8601String(),
                'last_replayed_at' => optional($this->last_replayed_at)?->toIso8601String(),
                'failed_at' => optional($this->failed_at)?->toIso8601String(),
                'error_message' => $this->error_message,
            ],
        ];
    }
}
