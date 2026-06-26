<?php

declare(strict_types=1);

namespace App\Http\Requests\Simulation;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdvanceDeliveryAssignmentSimulationRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'to_status' => $this->filled('to_status')
              ? Str::lower((string) $this->input('to_status'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'to_status' => [
                'nullable',
                'string',
                Rule::in((array) config('riders.simulation.flow', [])),
            ],
            'rider_public_id' => ['nullable', 'uuid', 'exists:riders,public_id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
