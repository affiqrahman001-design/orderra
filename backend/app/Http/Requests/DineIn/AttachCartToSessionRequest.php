<?php

declare(strict_types=1);

namespace App\Http\Requests\DineIn;

use App\Http\Requests\ApiRequest;

final class AttachCartToSessionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'cart_token' => ['required', 'string', 'max:100'],
        ];
    }
}
