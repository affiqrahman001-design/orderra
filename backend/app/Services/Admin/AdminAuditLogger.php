<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class AdminAuditLogger
{
    private const REDACTED = '[REDACTED]';

    /**
     * @var array<int,string>
     */
    private array $sensitiveKeyFragments = [
        'password',
        'secret',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'private_key',
        'client_secret',
        'card',
        'card_number',
        'pan',
        'cvv',
        'cvc',
        'expiry',
        'exp_month',
        'exp_year',
        'account_number',
        'routing_number',
    ];

    public function log(
        string $channel,
        string $action,
        string $status = 'completed',
        ?string $actorType = null,
        ?string $actorId = null,
        ?string $entityType = null,
        ?string $entityPublicId = null,
        ?string $entitySecondaryKey = null,
        ?string $summary = null,
        array $requestSnapshot = [],
        array $contextSnapshot = [],
        ?Carbon $occurredAt = null,
    ): AuditLog {
        /** @var Request|null $request */
        $request = request();

        return AuditLog::query()->create([
            'channel' => $channel,
            'action' => $action,
            'status' => $status,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'entity_type' => $entityType,
            'entity_public_id' => $entityPublicId,
            'entity_secondary_key' => $entitySecondaryKey,
            'summary' => $summary,
            'request_method' => $request?->method(),
            'request_path' => $request?->path(),
            'request_snapshot' => $this->sanitizeSnapshot($requestSnapshot),
            'context_snapshot' => $this->sanitizeSnapshot($contextSnapshot),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    public function logAdminAction(
        string $action,
        ?string $entityType = null,
        ?string $entityPublicId = null,
        ?string $entitySecondaryKey = null,
        ?string $summary = null,
        array $requestSnapshot = [],
        array $contextSnapshot = [],
        string $status = 'completed',
    ): AuditLog {
        return $this->log(
            channel: 'admin',
            action: $action,
            status: $status,
            actorType: 'admin',
            actorId: null,
            entityType: $entityType,
            entityPublicId: $entityPublicId,
            entitySecondaryKey: $entitySecondaryKey,
            summary: $summary,
            requestSnapshot: $requestSnapshot,
            contextSnapshot: $contextSnapshot,
        );
    }

    private function sanitizeSnapshot(array $snapshot): array
    {
        $clean = [];

        foreach ($snapshot as $key => $value) {
            $keyString = (string) $key;

            if ($this->isSensitiveKey($keyString)) {
                $clean[$keyString] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $clean[$keyString] = $this->sanitizeSnapshot($value);

                continue;
            }

            $clean[$keyString] = $value;
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        foreach ($this->sensitiveKeyFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
