<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refunds\StoreRefundRequest;
use App\Http\Requests\Refunds\StoreStandaloneRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Refunds\CreateRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

final class RefundController extends Controller
{
    public function store(
        StoreRefundRequest $request,
        Order $order,
        CreateRefundService $createRefundService,
    ): JsonResponse {
        $refund = $createRefundService->handle(
            order: $order,
            payload: $request->validated(),
            actorType: 'customer',
            actorId: null,
        );

        return $this->refundResponse($refund, $request);
    }

    public function storeStandalone(
        StoreStandaloneRefundRequest $request,
        CreateRefundService $createRefundService,
    ): JsonResponse {
        $validated = $request->validated();
        $order = $this->resolveOrder((string) $validated['order_id']);

        $refund = $createRefundService->handle(
            order: $order,
            payload: Arr::except($validated, ['order_id']),
            actorType: 'customer',
            actorId: null,
        );

        return $this->refundResponse($refund, $request);
    }

    public function show(Refund $refund): RefundResource
    {
        return new RefundResource(
            $refund->load([
                'order',
                'paymentIntent',
                'paymentTransaction',
                'events',
            ])
        );
    }

    private function resolveOrder(string $orderReference): Order
    {
        return Order::query()
            ->where('public_id', $orderReference)
            ->orWhere('order_code', $orderReference)
            ->when(ctype_digit($orderReference), fn ($query) => $query->orWhere('id', (int) $orderReference))
            ->firstOrFail();
    }

    private function refundResponse(Refund $refund, mixed $request): JsonResponse
    {
        $resource = new RefundResource($refund);
        $resolved = $resource->resolve($request);

        return response()->json([
            'message' => 'Refund request created successfully.',
            'data' => $resolved['data'],
        ], 201);
    }
}
