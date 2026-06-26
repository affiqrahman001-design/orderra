<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\PlaceOrderRequest;
use App\Http\Requests\Orders\TransitionOrderStatusRequest;
use App\Http\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderPlacementService;
use App\Services\Orders\OrderTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderPlacementService $orderPlacementService,
        private readonly OrderTransitionService $orderTransitionService,
    ) {}

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderPlacementService->placeFromCartToken(
            cartToken: (string) $request->validated('cart_token'),
            paymentIntentPublicId: (string) $request->validated('payment_intent_id'),
        );

        $resource = new OrderResource($order);
        $resolved = $resource->resolve($request);

        return response()->json($resolved, 201);
    }

    public function show(Request $request, string $orderPublicId): JsonResponse
    {
        $order = $this->resolveOrder($orderPublicId);
        $resolved = (new OrderResource($order))->resolve($request);

        return response()->json($resolved);
    }

    public function timeline(string $orderPublicId): JsonResponse
    {
        $order = $this->resolveOrder($orderPublicId);

        return response()->json([
            'data' => [
                'id' => $order->public_id,
                'order_code' => $order->order_code,
                'current_status' => $order->status,
                'timeline' => $order->statusHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'reason' => $history->reason,
                    'changed_by_type' => $history->changed_by_type,
                    'changed_by_id' => $history->changed_by_id,
                    'meta' => $history->meta ?? [],
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values(),
            ],
            'meta' => [
                'demo' => true,
            ],
        ]);
    }

    public function transition(
        TransitionOrderStatusRequest $request,
        string $orderPublicId,
    ): JsonResponse {
        $order = $this->resolveOrder($orderPublicId);

        $order = $this->orderTransitionService->transition(
            order: $order,
            toStatus: (string) $request->validated('to_status'),
            reason: $request->validated('reason'),
            meta: $request->validated('meta', []),
            actorType: 'admin',
            actorId: null,
        );

        $order = $order->loadMissing([
            'items',
            'fulfillment',
            'statusHistory',
            'paymentIntents',
            'refunds.events',
            'supportTickets.events',
            'deliveryAssignment.rider',
            'deliveryAssignment.trackingEvents',
        ]);

        $resolved = (new OrderResource($order))->resolve($request);

        return response()->json($resolved);
    }

    private function resolveOrder(string $orderPublicId): Order
    {
        return Order::with([
            'items',
            'fulfillment',
            'statusHistory',
            'paymentIntents',
            'refunds.events',
            'supportTickets.events',
            'deliveryAssignment.rider',
            'deliveryAssignment.trackingEvents',
        ])
            ->where('public_id', $orderPublicId)
            ->orWhere('order_code', $orderPublicId)
            ->firstOrFail();
    }
}
