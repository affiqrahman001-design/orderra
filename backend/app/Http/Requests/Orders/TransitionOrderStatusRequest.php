<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatus;
use App\Http\Requests\ApiRequest;

final class TransitionOrderStatusRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', 'in:'.implode(',', OrderStatus::values())],
            'reason' => ['nullable', 'string', 'max:1000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
