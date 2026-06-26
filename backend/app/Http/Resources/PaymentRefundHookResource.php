<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRefundHookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'payment_intent_id' => $this->payment_intent_id,
            'payment_transaction_id' => $this->payment_transaction_id,
            'hook_type' => $this->hook_type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'payload' => $this->payload,
            'notes' => $this->notes,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
