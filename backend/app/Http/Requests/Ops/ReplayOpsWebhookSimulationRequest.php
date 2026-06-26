<?php

declare(strict_types=1);

namespace App\Http\Requests\Ops;

use App\Http\Requests\ApiRequest;

final class ReplayOpsWebhookSimulationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
        ];
    }
}
