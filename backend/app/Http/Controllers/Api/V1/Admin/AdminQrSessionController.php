<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DineIn\QrSessionResource;
use App\Models\QrSession;
use App\Support\DineInJoinUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminQrSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QrSession::query()
            ->with(['restaurantTable', 'latestActiveSplitBillPlan'])
            ->withCount([
                'orders as linked_orders_count',
                'carts as linked_carts_count',
            ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('session_code', 'like', '%'.$search.'%')
                    ->orWhereHas('restaurantTable', function ($tableQuery) use ($search): void {
                        $tableQuery
                            ->where('code', 'like', '%'.$search.'%')
                            ->orWhere('label', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (QrSession $session) => $this->mapSummary($session)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $qrSessionId): QrSessionResource
    {
        $qrSession = $this->findSession($qrSessionId)->load([
            'restaurantTable',
            'events.order',
            'events.cart',
            'cartLinks.cart.placedOrder',
            'orderLinks.order',
            'orderLinks.cart',
            'latestActiveSplitBillPlan.participants',
        ]);

        return new QrSessionResource($qrSession);
    }

    private function findSession(string $sessionIdentifier): QrSession
    {
        return QrSession::query()
            ->where('public_id', $sessionIdentifier)
            ->orWhere('session_code', $sessionIdentifier)
            ->firstOrFail();
    }

    private function mapSummary(QrSession $session): array
    {
        return [
            'id' => $session->public_id,
            'session_code' => $session->session_code,
            'status' => $session->status,
            'party_size' => $session->party_size,
            'join_url' => DineInJoinUrl::build($session->session_code),
            'public_qr_url' => DineInJoinUrl::buildShortPublic($session->session_code),
            'table' => $session->restaurantTable ? [
                'id' => $session->restaurantTable->public_id,
                'code' => $session->restaurantTable->code,
                'label' => $session->restaurantTable->label,
            ] : null,
            'linked_orders_count' => (int) ($session->linked_orders_count ?? 0),
            'linked_carts_count' => (int) ($session->linked_carts_count ?? 0),
            'active_split_bill' => $session->latestActiveSplitBillPlan ? [
                'id' => $session->latestActiveSplitBillPlan->public_id,
                'status' => $session->latestActiveSplitBillPlan->status,
                'split_type' => $session->latestActiveSplitBillPlan->split_type,
            ] : null,
            'opened_at' => optional($session->opened_at)?->toIso8601String(),
            'last_activity_at' => optional($session->last_activity_at)?->toIso8601String(),
            'bill_requested_at' => optional($session->bill_requested_at)?->toIso8601String(),
            'closed_at' => optional($session->closed_at)?->toIso8601String(),
            'expires_at' => $this->sessionExpiresHint($session),
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
