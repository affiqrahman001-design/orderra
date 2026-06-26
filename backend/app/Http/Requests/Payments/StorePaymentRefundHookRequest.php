<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\RejectsSensitivePayloadFields;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRefundHookRequest extends ApiRequest
{
    use RejectsSensitivePayloadFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hook_type' => $this->filled('hook_type')
              ? Str::lower((string) $this->input('hook_type'))
              : null,
            'currency' => $this->filled('currency')
              ? Str::upper((string) $this->input('currency'))
              : config('payments.default_currency', 'USD'),
        ]);
    }

    public function rules(): array
    {
        return [
            'hook_type' => [
                'required',
                'string',
                Rule::in(config('payments.refund_hook_simulation.allowed_types', [])),
            ],
            'payment_transaction_id' => ['nullable', 'integer', 'exists:payment_transactions,id'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'reason' => ['nullable', 'string', 'max:120'],
            'payload' => ['nullable', 'array', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectSensitivePayloadFields($validator, $this->all());
        });
    }
}
