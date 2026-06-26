<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreSupportTicketRequest extends ApiRequest
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
            'order_id' => ['nullable', 'string', 'max:100'],
            'refund_id' => ['nullable', 'string', 'max:100'],
            'payment_intent_id' => ['nullable', 'string', 'max:100'],
            'delivery_assignment_id' => ['nullable', 'string', 'max:100'],
            'category' => [
                'required',
                'string',
                Rule::in((array) config('support.categories', [])),
            ],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'contact_snapshot' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $linkages = [
                $this->input('order_id'),
                $this->input('refund_id'),
                $this->input('payment_intent_id'),
                $this->input('delivery_assignment_id'),
            ];

            $hasAnyLinkage = collect($linkages)
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                ->isNotEmpty();

            if (! $hasAnyLinkage) {
                $validator->errors()->add(
                    'order_id',
                    'Sekurang-kurangnya satu linkage diperlukan: order, refund, payment intent, atau delivery assignment.'
                );
            }
        });
    }
}
