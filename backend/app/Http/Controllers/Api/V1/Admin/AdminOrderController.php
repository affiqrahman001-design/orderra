<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\TransitionOrderStatusRequest;
use App\Http\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminNotificationLogger;
use App\Services\Orders\OrderTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderTransitionService $orderTransitionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['fulfillment', 'deliveryAssignment.rider'])
            ->withCount('items');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($fulfillmentType = $request->string('fulfillment_type')->toString()) {
            $query->where('fulfillment_type', $fulfillmentType);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('order_code', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Order $order) => $this->mapSummary($order)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $orderPublicId): JsonResponse
    {
        $order = $this->findOrder($orderPublicId)->loadMissing([
            'items',
            'fulfillment',
            'statusHistory',
            'refunds.events',
            'supportTickets.events',
            'deliveryAssignment.rider',
            'deliveryAssignment.trackingEvents',
        ]);

        return response()->json((new OrderResource($order))->resolve($request));
    }

    public function transition(
        TransitionOrderStatusRequest $request,
        string $orderPublicId,
    ): JsonResponse {
        $order = $this->findOrder($orderPublicId)->loadMissing([
            'items',
            'fulfillment',
            'statusHistory',
            'refunds.events',
            'supportTickets.events',
            'deliveryAssignment.rider',
            'deliveryAssignment.trackingEvents',
        ]);

        $toStatus = (string) $request->validated('to_status');
        $reason = $request->validated('reason');

        $order = $this->orderTransitionService->transition(
            order: $order,
            toStatus: $toStatus,
            reason: $reason,
            meta: $request->validated('meta', []),
            actorType: 'admin',
            actorId: null,
        );

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'order.transition',
            entityType: 'order',
            entityPublicId: $order->public_id,
            entitySecondaryKey: $order->order_code,
            summary: sprintf('Order moved to %s.', $order->status),
            requestSnapshot: [
                'to_status' => $toStatus,
                'reason' => $reason,
            ],
            contextSnapshot: [
                'fulfillment_type' => $order->fulfillment_type,
                'status' => $order->status,
                'currency' => $order->currency,
            ],
        );

        app(AdminNotificationLogger::class)->logSimulated(
            channel: 'in_app',
            notificationType: 'order_status_changed',
            recipientType: 'customer',
            recipientKey: data_get($order->customer_context_snapshot, 'email')
            ?: data_get($order->customer_context_snapshot, 'phone')
            ?: data_get($order->customer_context_snapshot, 'name'),
            entityType: 'order',
            entityPublicId: $order->public_id,
            subject: 'Order status updated',
            title: 'Order '.$order->order_code,
            bodyPreview: sprintf('Status order telah dikemas kini ke %s.', $order->status),
            meta: [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'fulfillment_type' => $order->fulfillment_type,
            ],
        );

        $fresh = $order->loadMissing([
            'items',
            'fulfillment',
            'statusHistory',
            'refunds.events',
            'supportTickets.events',
            'deliveryAssignment.rider',
            'deliveryAssignment.trackingEvents',
        ]);

        return response()->json((new OrderResource($fresh))->resolve($request));
    }

    private function findOrder(string $orderPublicId): Order
    {
        return Order::query()
            ->where('public_id', $orderPublicId)
            ->orWhere('order_code', $orderPublicId)
            ->firstOrFail();
    }

    private function mapSummary(Order $order): array
    {
        return [
            'id' => $order->public_id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'fulfillment_type' => $order->fulfillment_type,
            'source' => $order->source,
            'currency' => $order->currency,
            'item_count' => (int) ($order->items_count ?? 0),
            'total_amount' => $this->toMoney((int) $order->total_amount),
            'customer_name' => data_get($order->customer_context_snapshot, 'name'),
            'allowed_transitions' => $order->allowedTransitions(),
            'delivery_assignment' => $order->deliveryAssignment ? [
                'id' => $order->deliveryAssignment->public_id,
                'status' => $order->deliveryAssignment->status,
                'rider_name' => $order->deliveryAssignment->rider?->name,
            ] : null,
            'placed_at' => optional($order->placed_at)?->toIso8601String(),
            'completed_at' => optional($order->completed_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('admin.pagination.default_per_page', 15);
        $max = (int) config('admin.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
