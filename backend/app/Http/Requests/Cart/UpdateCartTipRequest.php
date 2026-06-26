<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

final class UpdateCartTipRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'tip_type' => ['required', 'string', 'in:none,amount,percentage'],
            'tip_value' => ['required', 'integer', 'min:0'],
        ];
    }
}
