<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Refund::query()->with(['order', 'paymentIntent']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($orderQuery) use ($search): void {
                        $orderQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('order_code', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Refund $refund) => $this->mapSummary($refund)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
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

    private function mapSummary(Refund $refund): array
    {
        return [
            'id' => $refund->public_id,
            'category' => $refund->category,
            'status' => $refund->status,
            'resolution_type' => $refund->resolution_type,
            'currency' => $refund->currency,
            'requested_amount' => $this->toMoney((int) $refund->requested_amount),
            'approved_amount' => $refund->approved_amount !== null
              ? $this->toMoney((int) $refund->approved_amount)
              : null,
            'resolved_amount' => $refund->resolved_amount !== null
              ? $this->toMoney((int) $refund->resolved_amount)
              : null,
            'order' => $refund->order ? [
                'id' => $refund->order->public_id,
                'order_code' => $refund->order->order_code,
                'status' => $refund->order->status,
            ] : null,
            'payment_intent_id' => $refund->paymentIntent?->public_id,
            'requested_at' => optional($refund->requested_at)?->toIso8601String(),
            'reviewed_at' => optional($refund->reviewed_at)?->toIso8601String(),
            'resolved_at' => optional($refund->resolved_at)?->toIso8601String(),
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
