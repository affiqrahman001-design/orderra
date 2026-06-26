<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class NotificationLog extends Model
{
    protected $fillable = [
        'public_id',
        'channel',
        'notification_type',
        'status',
        'provider_code',
        'recipient_type',
        'recipient_key',
        'entity_type',
        'entity_public_id',
        'subject',
        'title',
        'body_preview',
        'meta',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $log): void {
            if (! $log->public_id) {
                $log->public_id = (string) Str::uuid();
            }
        });
    }
}
