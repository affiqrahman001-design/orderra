<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
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
                    ->orWhere('action', 'like', '%'.$search.'%')
                    ->orWhere('entity_public_id', 'like', '%'.$search.'%')
                    ->orWhere('entity_secondary_key', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->latest('occurred_at')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (AuditLog $log) => $this->mapSummary($log)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        return new AuditLogResource($auditLog);
    }

    private function mapSummary(AuditLog $log): array
    {
        return [
            'id' => $log->public_id,
            'channel' => $log->channel,
            'action' => $log->action,
            'status' => $log->status,
            'actor_type' => $log->actor_type,
            'entity_type' => $log->entity_type,
            'entity_public_id' => $log->entity_public_id,
            'entity_secondary_key' => $log->entity_secondary_key,
            'summary' => $log->summary,
            'occurred_at' => optional($log->occurred_at)?->toIso8601String(),
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
