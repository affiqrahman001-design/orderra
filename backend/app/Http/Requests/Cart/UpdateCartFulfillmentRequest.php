<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

final class UpdateCartFulfillmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'fulfillment_type' => ['required', 'string', 'in:delivery,pickup,dine_in'],
            'fulfillment_context' => ['nullable', 'array'],
            'customer_context' => ['nullable', 'array'],
        ];
    }
}
