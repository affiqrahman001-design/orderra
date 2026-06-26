<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_type' => $this->transaction_type,
            'direction' => $this->direction,
            'status' => $this->enumValue($this->status),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider_reference' => $this->provider_reference,
            'external_reference' => $this->external_reference,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function enumValue(mixed $value): mixed
    {
        return is_object($value) && property_exists($value, 'value')
          ? $value->value
          : $value;
    }
}
