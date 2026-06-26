<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SimulatePaymentIntentRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'simulation_outcome' => $this->filled('simulation_outcome')
              ? Str::lower((string) $this->input('simulation_outcome'))
              : config('payments.simulation.default_outcome', 'success'),
        ]);
    }

    public function rules(): array
    {
        return [
            'simulation_outcome' => ['required', 'string', Rule::in(config('payments.simulation.allowed_outcomes', ['success', 'failed', 'pending']))],
        ];
    }
}
