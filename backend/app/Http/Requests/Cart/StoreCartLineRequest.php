<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

final class StoreCartLineRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'menu_item_id' => ['required', 'string', 'max:100'],
            'variant_id' => ['nullable', 'string', 'max:100'],

            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:500'],

            'selected_modifiers' => ['nullable', 'array', 'max:20'],
            'selected_modifiers.*.id' => ['required_with:selected_modifiers', 'string', 'max:100', 'distinct'],
        ];
    }
}
