<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulation\AdvanceDeliveryAssignmentSimulationRequest;
use App\Http\Requests\Simulation\StoreDeliveryAssignmentSimulationRequest;
use App\Http\Resources\DeliveryAssignmentResource;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\Rider;
use App\Services\Riders\RiderSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminDeliveryAssignmentController extends Controller
{
    public function riders(Request $request): JsonResponse
    {
        $query = Rider::query();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('rider_code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderBy('type')
            ->orderBy('rider_code')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Rider $rider) => $this->mapRiderSummary($rider)
            )->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function riderPool(): JsonResponse
    {
        $riders = Rider::query()
            ->orderBy('type')
            ->orderBy('rider_code')
            ->get();

        return response()->json([
            'data' => $riders->map(fn (Rider $rider) => $this->mapRiderSummary($rider))->values(),
            'meta' => [
                'simulation_flow' => (array) config('riders.simulation.flow', []),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryAssignment::query()
            ->with(['order', 'rider'])
            ->withCount('trackingEvents');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($providerType = $request->string('provider_type')->toString()) {
            $query->where('provider_type', $providerType);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($orderQuery) use ($search): void {
                        $orderQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('order_code', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('rider', function ($riderQuery) use ($search): void {
                        $riderQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('rider_code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (DeliveryAssignment $assignment) => $this->mapAssignmentSummary($assignment)
            )->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function show(DeliveryAssignment $deliveryAssignment): DeliveryAssignmentResource
    {
        return new DeliveryAssignmentResource(
            $deliveryAssignment->load([
                'order',
                'rider',
                'trackingEvents',
            ])
        );
    }

    public function assign(
        StoreDeliveryAssignmentSimulationRequest $request,
        Order $order,
        RiderSimulationService $riderSimulationService,
    ): JsonResponse {
        $assignment = $riderSimulationService->createAssignment(
            order: $order,
            payload: $request->validated(),
        );

        $resource = new DeliveryAssignmentResource($assignment);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Admin rider assignment simulation created successfully.',
            'data' => $resolved['data'],
        ], 201);
    }

    public function advance(
        AdvanceDeliveryAssignmentSimulationRequest $request,
        DeliveryAssignment $deliveryAssignment,
        RiderSimulationService $riderSimulationService,
    ): JsonResponse {
        $assignment = $riderSimulationService->advance(
            assignment: $deliveryAssignment,
            payload: $request->validated(),
        );

        $resource = new DeliveryAssignmentResource($assignment);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Admin rider assignment simulation advanced successfully.',
            'data' => $resolved['data'],
        ]);
    }

    private function mapRiderSummary(Rider $rider): array
    {
        return [
            'id' => $rider->public_id,
            'rider_code' => $rider->rider_code,
            'name' => $rider->name,
            'phone' => $rider->phone,
            'type' => $rider->type,
            'status' => $rider->status,
            'vehicle_type' => $rider->vehicle_type,
            'is_demo' => (bool) $rider->is_demo,
            'created_at' => optional($rider->created_at)?->toIso8601String(),
        ];
    }

    private function mapAssignmentSummary(DeliveryAssignment $assignment): array
    {
        return [
            'id' => $assignment->public_id,
            'status' => $assignment->status,
            'provider_type' => $assignment->provider_type,
            'eta_minutes' => $assignment->eta_minutes,
            'tracking_events_count' => (int) ($assignment->tracking_events_count ?? 0),
            'order' => $assignment->order ? [
                'id' => $assignment->order->public_id,
                'order_code' => $assignment->order->order_code,
                'status' => $assignment->order->status,
            ] : null,
            'rider' => $assignment->rider ? [
                'id' => $assignment->rider->public_id,
                'rider_code' => $assignment->rider->rider_code,
                'name' => $assignment->rider->name,
                'type' => $assignment->rider->type,
            ] : null,
            'assigned_at' => optional($assignment->assigned_at)?->toIso8601String(),
            'delivered_at' => optional($assignment->delivered_at)?->toIso8601String(),
        ];
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
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
