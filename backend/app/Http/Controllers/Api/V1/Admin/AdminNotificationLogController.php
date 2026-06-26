<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\NotificationLogResource;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminNotificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::query();

        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }

        if ($notificationType = $request->string('notification_type')->toString()) {
            $query->where('notification_type', $notificationType);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($entityType = $request->string('entity_type')->toString()) {
            $query->where('entity_type', $entityType);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('notification_type', 'like', '%'.$search.'%')
                    ->orWhere('recipient_key', 'like', '%'.$search.'%')
                    ->orWhere('entity_public_id', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (NotificationLog $log) => $this->mapSummary($log)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $notificationLogId): NotificationLogResource
    {
        return new NotificationLogResource(
            $this->findNotificationLog($notificationLogId)
        );
    }

    private function findNotificationLog(string $notificationLogId): NotificationLog
    {
        $normalized = trim($notificationLogId);

        $normalized = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2015}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u', '-', $normalized);
        $normalized = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $normalized);

        return NotificationLog::query()
            ->where('public_id', $normalized)
            ->firstOrFail();
    }

    private function mapSummary(NotificationLog $log): array
    {
        return [
            'id' => $log->public_id,
            'channel' => $log->channel,
            'notification_type' => $log->notification_type,
            'status' => $log->status,
            'recipient_type' => $log->recipient_type,
            'recipient_key' => $log->recipient_key,
            'entity_type' => $log->entity_type,
            'entity_public_id' => $log->entity_public_id,
            'title' => $log->title,
            'subject' => $log->subject,
            'sent_at' => optional($log->sent_at)?->toIso8601String(),
            'failed_at' => optional($log->failed_at)?->toIso8601String(),
            'created_at' => optional($log->created_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('admin_ops.pagination.default_per_page', 15);
        $max = (int) config('admin_ops.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
