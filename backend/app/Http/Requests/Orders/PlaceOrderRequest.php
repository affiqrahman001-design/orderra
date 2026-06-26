<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Http\Requests\ApiRequest;

final class PlaceOrderRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cart_token' => $this->input('cart_token') ?: $this->header(config('cart.token_header')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cart_token' => ['required', 'string', 'max:100'],
            'payment_intent_id' => ['required', 'string', 'max:100'],
        ];
    }
}
