<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource;

        return [
            'data' => [
                'id' => $log->public_id,
                'channel' => $log->channel,
                'action' => $log->action,
                'status' => $log->status,
                'actor_type' => $log->actor_type,
                'actor_id' => $log->actor_id,
                'entity_type' => $log->entity_type,
                'entity_public_id' => $log->entity_public_id,
                'entity_secondary_key' => $log->entity_secondary_key,
                'summary' => $log->summary,
                'request_method' => $log->request_method,
                'request_path' => $log->request_path,
                'request_snapshot' => $log->request_snapshot ?? [],
                'context_snapshot' => $log->context_snapshot ?? [],
                'occurred_at' => optional($log->occurred_at)?->toIso8601String(),
                'created_at' => optional($log->created_at)?->toIso8601String(),
                'updated_at' => optional($log->updated_at)?->toIso8601String(),
            ],
        ];
    }
}
