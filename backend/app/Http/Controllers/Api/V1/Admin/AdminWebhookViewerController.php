<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OpsWebhookEventResource;
use App\Models\OpsWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminWebhookViewerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OpsWebhookEvent::query()
            ->with(['order', 'refund', 'paymentIntent', 'deliveryAssignment']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($aggregateType = $request->string('aggregate_type')->toString()) {
            $query->where('aggregate_type', $aggregateType);
        }

        if ($eventName = $request->string('event_name')->toString()) {
            $query->where('event_name', $eventName);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('event_name', 'like', '%'.$search.'%')
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
                fn (OpsWebhookEvent $event) => $this->mapSummary($event)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(OpsWebhookEvent $opsWebhookEvent): OpsWebhookEventResource
    {
        return new OpsWebhookEventResource(
            $opsWebhookEvent->load([
                'order',
                'refund',
                'paymentIntent',
                'deliveryAssignment',
            ])
        );
    }

    private function mapSummary(OpsWebhookEvent $event): array
    {
        return [
            'id' => $event->public_id,
            'event_name' => $event->event_name,
            'aggregate_type' => $event->aggregate_type,
            'status' => $event->status,
            'replay_count' => (int) $event->replay_count,
            'links' => [
                'order_code' => $event->order?->order_code,
                'refund_id' => $event->refund?->public_id,
                'payment_intent_id' => $event->paymentIntent?->public_id,
                'delivery_assignment_id' => $event->deliveryAssignment?->public_id,
            ],
            'generated_at' => optional($event->generated_at)?->toIso8601String(),
            'last_replayed_at' => optional($event->last_replayed_at)?->toIso8601String(),
            'failed_at' => optional($event->failed_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('admin.pagination.default_per_page', 15);
        $max = (int) config('admin.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
