<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Http\Requests\Concerns\RejectsSensitivePayloadFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimulatePaymentWebhookRequest extends FormRequest
{
    use RejectsSensitivePayloadFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_name' => $this->filled('event_name')
              ? Str::lower((string) $this->input('event_name'))
              : null,
            'provider_code' => $this->filled('provider_code')
              ? Str::lower((string) $this->input('provider_code'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'event_name' => [
                'required',
                'string',
                Rule::in(config('payments.webhook_simulation.allowed_events', [])),
            ],
            'provider_code' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('payments.providers', []))),
            ],
            'provider_reference' => ['nullable', 'string', 'max:100'],
            'headers' => ['nullable', 'array', 'max:20'],
            'headers.*' => ['nullable', 'string', 'max:500'],
            'payload' => ['nullable', 'array', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectSensitivePayloadFields($validator, $this->all());
        });
    }
}
