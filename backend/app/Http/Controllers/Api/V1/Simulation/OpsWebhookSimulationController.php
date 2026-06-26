<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Simulation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ops\ReplayOpsWebhookSimulationRequest;
use App\Http\Requests\Ops\StoreOpsWebhookSimulationRequest;
use App\Http\Resources\OpsWebhookEventResource;
use App\Models\OpsWebhookEvent;
use App\Services\Ops\OpsWebhookEventService;
use App\Services\Ops\OpsWebhookReplayService;
use Illuminate\Http\JsonResponse;

final class OpsWebhookSimulationController extends Controller
{
    public function store(
        StoreOpsWebhookSimulationRequest $request,
        OpsWebhookEventService $opsWebhookEventService,
    ): JsonResponse {
        $event = $opsWebhookEventService->emit(
            eventName: (string) $request->validated('event_name'),
            context: [
                'order_id' => $request->validated('order_id'),
                'refund_id' => $request->validated('refund_id'),
                'payment_intent_id' => $request->validated('payment_intent_id'),
                'delivery_assignment_id' => $request->validated('delivery_assignment_id'),
            ],
            payload: $request->validated('payload', []),
            headers: $request->validated('headers', []),
            notes: $request->validated('notes'),
        );

        $resource = new OpsWebhookEventResource($event);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Ops webhook event created successfully.',
            'data' => $resolved['data'],
        ], 201);
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

    public function replay(
        ReplayOpsWebhookSimulationRequest $request,
        OpsWebhookEvent $opsWebhookEvent,
        OpsWebhookReplayService $opsWebhookReplayService,
    ): JsonResponse {
        $event = $opsWebhookReplayService->replay(
            event: $opsWebhookEvent,
            note: $request->validated('note'),
        );

        $resource = new OpsWebhookEventResource($event);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Ops webhook event replayed successfully.',
            'data' => $resolved['data'],
        ]);
    }
}
