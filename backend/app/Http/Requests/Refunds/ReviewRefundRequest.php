<?php

declare(strict_types=1);

namespace App\Http\Requests\Refunds;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ReviewRefundRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'decision' => $this->filled('decision')
              ? Str::lower((string) $this->input('decision'))
              : null,
            'resolution_type' => $this->filled('resolution_type')
              ? Str::lower((string) $this->input('resolution_type'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                Rule::in(['under_review', 'approve', 'reject']),
            ],
            'approved_amount' => ['nullable', 'integer', 'min:1'],
            'resolution_type' => [
                'nullable',
                'string',
                Rule::in((array) config('refunds.resolution_types', [])),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
