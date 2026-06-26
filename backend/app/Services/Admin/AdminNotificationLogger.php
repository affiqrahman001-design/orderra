<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\NotificationLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class AdminNotificationLogger
{
    public function log(
        string $channel,
        string $notificationType,
        string $status = 'simulated',
        ?string $providerCode = null,
        ?string $recipientType = null,
        ?string $recipientKey = null,
        ?string $entityType = null,
        ?string $entityPublicId = null,
        ?string $subject = null,
        ?string $title = null,
        ?string $bodyPreview = null,
        array $meta = [],
        ?string $errorMessage = null,
        ?CarbonInterface $sentAt = null,
        ?CarbonInterface $failedAt = null,
    ): NotificationLog {
        $limit = (int) config('admin_ops.notifications.body_preview_limit', 180);

        return NotificationLog::query()->create([
            'channel' => $channel,
            'notification_type' => $notificationType,
            'status' => $status,
            'provider_code' => $providerCode,
            'recipient_type' => $recipientType,
            'recipient_key' => $recipientKey,
            'entity_type' => $entityType,
            'entity_public_id' => $entityPublicId,
            'subject' => $subject,
            'title' => $title,
            'body_preview' => $bodyPreview !== null
              ? Str::limit(trim($bodyPreview), $limit, '')
              : null,
            'meta' => $meta,
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
            'failed_at' => $failedAt,
        ]);
    }

    public function logSimulated(
        string $channel,
        string $notificationType,
        ?string $recipientType = null,
        ?string $recipientKey = null,
        ?string $entityType = null,
        ?string $entityPublicId = null,
        ?string $subject = null,
        ?string $title = null,
        ?string $bodyPreview = null,
        array $meta = [],
        ?string $providerCode = null,
    ): NotificationLog {
        return $this->log(
            channel: $channel,
            notificationType: $notificationType,
            status: 'simulated',
            providerCode: $providerCode,
            recipientType: $recipientType,
            recipientKey: $recipientKey,
            entityType: $entityType,
            entityPublicId: $entityPublicId,
            subject: $subject,
            title: $title,
            bodyPreview: $bodyPreview,
            meta: $meta,
            sentAt: now(),
        );
    }
}
