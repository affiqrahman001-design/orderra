<?php

declare(strict_types=1);

namespace App\Http\Requests\DineIn;

use App\Http\Requests\ApiRequest;

final class CreateSplitBillPlanRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'split_type' => ['required', 'string', 'in:equal,by_item'],

            'participants' => ['required', 'array', 'min:2', 'max:'.(string) config('dine_in.split_bill.max_participants', 12)],
            'participants.*.participant_ref' => ['required', 'string', 'max:40', 'distinct'],
            'participants.*.display_name' => ['required', 'string', 'max:80'],
            'participants.*.seat_label' => ['nullable', 'string', 'max:40'],
            'participants.*.is_primary_payer' => ['nullable', 'boolean'],

            'item_allocations' => ['nullable', 'array'],
            'item_allocations.*.participant_ref' => ['required_with:item_allocations', 'string', 'max:40'],
            'item_allocations.*.order_item_id' => ['required_with:item_allocations', 'integer', 'min:1'],
            'item_allocations.*.quantity' => ['required_with:item_allocations', 'integer', 'min:1'],
        ];
    }
}
