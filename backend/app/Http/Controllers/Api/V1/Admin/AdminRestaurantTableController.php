<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RotateTableQrSessionRequest;
use App\Http\Resources\DineIn\QrSessionResource;
use App\Models\QrSession;
use App\Models\RestaurantTable;
use App\Services\Admin\AdminAuditLogger;
use App\Services\DineIn\DineInSessionService;
use App\Support\DineInJoinUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminRestaurantTableController extends Controller
{
    public function __construct(
        private readonly DineInSessionService $dineInSessionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = RestaurantTable::query()
            ->with(['branch', 'latestActiveQrSession'])
            ->where('status', 'active')
            ->orderBy('code');

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('label', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (RestaurantTable $table) => $this->mapTableRow($table)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function rotateQr(RotateTableQrSessionRequest $request, string $tablePublicId): QrSessionResource
    {
        $table = RestaurantTable::query()->where('public_id', $tablePublicId)->firstOrFail();

        $validated = $request->validated();
        $session = $this->dineInSessionService->rotateQrSessionForTable(
            $table,
            [
                'source' => 'admin_rotate',
                'party_size' => $validated['party_size'] ?? null,
            ],
            isset($validated['note']) ? (string) $validated['note'] : null,
        );

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'dine_in.table.qr_rotate',
            entityType: 'restaurant_table',
            entityPublicId: $table->public_id,
            entitySecondaryKey: $table->code,
            summary: sprintf('Table QR session refreshed (%s).', $session->session_code),
            requestSnapshot: [
                'party_size' => $validated['party_size'] ?? null,
            ],
            contextSnapshot: [
                'session_code' => $session->session_code,
                'session_public_id' => $session->public_id,
            ],
        );

        return new QrSessionResource($session);
    }

    private function mapTableRow(RestaurantTable $table): array
    {
        $active = $table->latestActiveQrSession;

        return [
            'id' => $table->public_id,
            'code' => $table->code,
            'label' => $table->label,
            'seat_capacity' => $table->seat_capacity,
            'status' => $table->status,
            'branch' => $table->branch ? [
                'id' => $table->branch->public_id,
                'code' => $table->branch->code,
                'name' => $table->branch->name,
            ] : null,
            'active_qr_session' => $active instanceof QrSession ? [
                'id' => $active->public_id,
                'session_code' => $active->session_code,
                'status' => $active->status,
                'join_url' => DineInJoinUrl::build($active->session_code),
                'public_qr_url' => DineInJoinUrl::buildShortPublic($active->session_code),
                'party_size' => $active->party_size,
                'opened_at' => optional($active->opened_at)?->toIso8601String(),
                'last_activity_at' => optional($active->last_activity_at)?->toIso8601String(),
                'expires_at' => $this->sessionExpiresHint($active),
            ] : null,
        ];
    }

    private function sessionExpiresHint(QrSession $session): ?string
    {
        $ttl = config('dine_in.qr_sessions.demo_session_ttl_hours');

        if ($ttl === null || $ttl === '' || $session->opened_at === null) {
            return null;
        }

        if (! $session->isActive()) {
            return optional($session->closed_at)?->toIso8601String();
        }

        return optional($session->opened_at)?->copy()->addHours((int) $ttl)->toIso8601String();
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('admin.pagination.default_per_page', 15);
        $max = (int) config('admin.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
