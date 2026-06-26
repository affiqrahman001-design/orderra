<?php

declare(strict_types=1);

namespace App\Http\Requests\Refunds;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreRefundRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category' => $this->filled('category')
              ? Str::lower((string) $this->input('category'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'category' => [
                'required',
                'string',
                Rule::in((array) config('refunds.categories', [])),
            ],
            'payment_intent_id' => ['nullable', 'integer', 'exists:payment_intents,id'],
            'payment_transaction_id' => ['nullable', 'integer', 'exists:payment_transactions,id'],
            'requested_amount' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'context_snapshot' => ['nullable', 'array'],
        ];
    }
}
