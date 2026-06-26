<?php

declare(strict_types=1);

namespace App\Http\Requests\DineIn;

use App\Http\Requests\ApiRequest;

final class OpenDineInSessionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'table_code' => ['nullable', 'string', 'max:40', 'required_without:table_label'],
            'table_label' => ['nullable', 'string', 'max:100', 'required_without:table_code'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'cart_token' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:30'],
        ];
    }
}
