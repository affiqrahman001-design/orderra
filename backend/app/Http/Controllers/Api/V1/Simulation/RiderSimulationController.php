<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Simulation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulation\AdvanceDeliveryAssignmentSimulationRequest;
use App\Http\Requests\Simulation\StoreDeliveryAssignmentSimulationRequest;
use App\Http\Resources\DeliveryAssignmentResource;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Services\Riders\RiderSimulationService;
use Illuminate\Http\JsonResponse;

final class RiderSimulationController extends Controller
{
    public function store(
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
            'message' => 'Delivery assignment simulation created successfully.',
            'data' => $resolved['data'],
        ], 201);
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
            'message' => 'Delivery assignment simulation advanced successfully.',
            'data' => $resolved['data'],
        ]);
    }
}
