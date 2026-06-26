<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentIntentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'public_id' => $this->public_id,
            'internal_id' => $this->id,
            'intent_code' => $this->intent_code,
            'cart_id' => $this->cart_id,
            'order_id' => $this->order_id,
            'method_code' => $this->enumValue($this->method_code),
            'provider_code' => $this->enumValue($this->provider_code),
            'status' => $this->enumValue($this->status),
            'country_code' => $this->country_code,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'branch_code' => $this->branch_code,
            'simulation_context' => $this->simulation_context,
            'provider_context' => $this->provider_context,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_attempted_at' => $this->last_attempted_at?->toIso8601String(),
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'succeeded_at' => $this->succeeded_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'attempts' => PaymentAttemptResource::collection($this->whenLoaded('attempts')),
            'transactions' => PaymentTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }

    protected function enumValue(mixed $value): mixed
    {
        return is_object($value) && property_exists($value, 'value')
          ? $value->value
          : $value;
    }
}
