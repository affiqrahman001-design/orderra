<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\RejectsSensitivePayloadFields;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentIntentRequest extends ApiRequest
{
    use RejectsSensitivePayloadFields {
        isSensitivePayloadKey as private baseSensitivePayloadKey;
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'method_code' => $this->filled('method_code') ? Str::lower((string) $this->input('method_code')) : null,
            'provider_code' => $this->filled('provider_code') ? Str::lower((string) $this->input('provider_code')) : null,
            'country_code' => $this->filled('country_code') ? Str::upper((string) $this->input('country_code')) : config('payments.default_country', 'US'),
            'currency' => $this->filled('currency') ? Str::upper((string) $this->input('currency')) : config('payments.default_currency', 'USD'),
            'simulation_outcome' => $this->filled('simulation_outcome')
              ? Str::lower((string) $this->input('simulation_outcome'))
              : config('payments.simulation.default_outcome', 'success'),
        ]);
    }

    public function rules(): array
    {
        return [
            'method_code' => ['required', 'string', Rule::in(array_keys(config('payments.methods', [])))],
            'provider_code' => ['nullable', 'string', Rule::in(array_keys(config('payments.providers', [])))],
            'country_code' => ['required', 'string', 'size:2', Rule::in(array_keys(config('payments.country_capabilities', [])))],
            'currency' => ['required', 'string', 'size:3'],

            'cart_token' => ['nullable', 'string', 'max:100'],
            'cart_id' => ['nullable', 'integer', 'exists:carts,id'],
            'cart_public_id' => ['nullable', 'string', 'max:100'],

            'amount' => ['nullable', 'integer', 'min:1', 'required_without_all:cart_token,cart_id,cart_public_id'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'simulation_outcome' => ['nullable', 'string', Rule::in(config('payments.simulation.allowed_outcomes', ['success', 'failed', 'pending']))],
            'meta' => ['nullable', 'array', 'max:25'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectSensitivePayloadFields($validator, $this->all());
        });
    }

    protected function isSensitivePayloadKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        if (in_array($normalized, ['cart_token', 'x_cart_token'], true)) {
            return false;
        }

        return $this->baseSensitivePayloadKey($key);
    }
}
