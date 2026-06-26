<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AuditLog extends Model
{
    protected $fillable = [
        'public_id',
        'channel',
        'action',
        'status',
        'actor_type',
        'actor_id',
        'entity_type',
        'entity_public_id',
        'entity_secondary_key',
        'summary',
        'request_method',
        'request_path',
        'request_snapshot',
        'context_snapshot',
        'occurred_at',
    ];

    protected $casts = [
        'request_snapshot' => 'array',
        'context_snapshot' => 'array',
        'occurred_at' => 'datetime',
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
