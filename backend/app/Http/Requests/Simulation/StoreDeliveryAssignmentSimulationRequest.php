<?php

declare(strict_types=1);

namespace App\Http\Requests\Simulation;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreDeliveryAssignmentSimulationRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider_type' => $this->filled('provider_type')
              ? Str::lower((string) $this->input('provider_type'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'provider_type' => [
                'nullable',
                'string',
                Rule::in((array) config('riders.types', [])),
            ],
            'rider_public_id' => ['nullable', 'uuid', 'exists:riders,public_id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
