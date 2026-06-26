<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentWebhookEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'payment_intent_id' => $this->payment_intent_id,
            'provider_code' => $this->provider_code,
            'event_name' => $this->event_name,
            'delivery_status' => $this->delivery_status,
            'provider_reference' => $this->provider_reference,
            'headers' => $this->headers,
            'payload' => $this->payload,
            'received_at' => $this->received_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
