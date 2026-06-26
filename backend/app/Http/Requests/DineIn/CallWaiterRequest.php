<?php

declare(strict_types=1);

namespace App\Http\Requests\DineIn;

use App\Http\Requests\ApiRequest;

final class CallWaiterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
