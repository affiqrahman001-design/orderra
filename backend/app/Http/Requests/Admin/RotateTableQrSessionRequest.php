<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

final class RotateTableQrSessionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'party_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
