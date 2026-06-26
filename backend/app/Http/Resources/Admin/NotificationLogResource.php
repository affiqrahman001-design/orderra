<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource;

        return [
            'data' => [
                'id' => $log->public_id,
                'channel' => $log->channel,
                'notification_type' => $log->notification_type,
                'status' => $log->status,
                'provider_code' => $log->provider_code,
                'recipient_type' => $log->recipient_type,
                'recipient_key' => $log->recipient_key,
                'entity_type' => $log->entity_type,
                'entity_public_id' => $log->entity_public_id,
                'subject' => $log->subject,
                'title' => $log->title,
                'body_preview' => $log->body_preview,
                'meta' => $log->meta ?? [],
                'error_message' => $log->error_message,
                'sent_at' => optional($log->sent_at)?->toIso8601String(),
                'failed_at' => optional($log->failed_at)?->toIso8601String(),
                'created_at' => optional($log->created_at)?->toIso8601String(),
                'updated_at' => optional($log->updated_at)?->toIso8601String(),
            ],
        ];
    }
}
