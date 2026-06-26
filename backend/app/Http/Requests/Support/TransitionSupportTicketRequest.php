<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class TransitionSupportTicketRequest extends ApiRequest
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
                'required',
                'string',
                Rule::in((array) config('support.statuses', [])),
            ],
            'note' => ['nullable', 'string'],
            'resolution_summary' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
