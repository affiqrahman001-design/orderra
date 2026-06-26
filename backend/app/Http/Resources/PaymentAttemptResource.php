<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->enumValue($this->status),
            'amount' => $this->amount,
            'simulation_outcome' => $this->simulation_outcome,
            'provider_reference' => $this->provider_reference,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'initiated_at' => $this->initiated_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
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
