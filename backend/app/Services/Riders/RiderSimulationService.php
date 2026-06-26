<?php

declare(strict_types=1);

namespace App\Services\Riders;

use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\Rider;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Ops\OpsWebhookEventService;
use App\Services\Orders\OrderTransitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RiderSimulationService
{
    public function __construct(
        protected OrderTransitionService $orderTransitionService,
        protected OpsWebhookEventService $opsWebhookEventService,
    ) {}

    public function createAssignment(Order $order, array $payload = []): DeliveryAssignment
    {
        $this->assertDeliveryOrder($order);
        $this->assertAssignableOrderStatus($order);

        $existing = DeliveryAssignment::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            return $existing->fresh([
                'order',
                'rider',
                'trackingEvents',
            ]);
        }

        return DB::transaction(function () use ($order, $payload): DeliveryAssignment {
            $activationStatus = (string) config('riders.simulation.activation_status', 'awaiting_rider');
            $providerType = (string) ($payload['provider_type'] ?? 'self');

            $assignment = DeliveryAssignment::query()->create([
                'public_id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'rider_id' => null,
                'provider_type' => $providerType,
                'status' => $activationStatus,
                'eta_minutes' => $this->etaFor($activationStatus),
                'context_snapshot' => [
                    'order_status_at_creation' => $order->status,
                    'fulfillment_type' => $order->fulfillment_type,
                ],
                'meta' => [
                    'preferred_rider_public_id' => $payload['rider_public_id'] ?? null,
                ],
            ]);

            $order = $order->fresh(['items', 'fulfillment', 'statusHistory']);

            if ($order->status !== $activationStatus && in_array($activationStatus, $order->allowedTransitions(), true)) {
                $order = $this->orderTransitionService->transition(
                    order: $order,
                    toStatus: $activationStatus,
                    reason: 'Delivery assignment simulation started.',
                    meta: [
                        'delivery_assignment_public_id' => $assignment->public_id,
                        'provider_type' => $providerType,
                    ],
                    actorType: 'system',
                    actorId: null,
                );
            }

            $this->recordTrackingEvent(
                assignment: $assignment,
                status: $activationStatus,
                note: $payload['note'] ?? 'Delivery assignment created.',
                payload: [
                    'provider_type' => $providerType,
                ],
            );

            $assignment = $assignment->fresh([
                'order',
                'rider',
                'trackingEvents',
            ]);

            $this->emitOpsEventsForStatus($assignment, $activationStatus);

            app(AdminAuditLogger::class)->log(
                channel: 'simulation',
                action: 'rider.assignment.created',
                status: 'completed',
                actorType: 'system',
                actorId: null,
                entityType: 'delivery_assignment',
                entityPublicId: $assignment->public_id,
                entitySecondaryKey: $assignment->order?->order_code,
                summary: 'Demo rider assignment created.',
                requestSnapshot: [
                    'provider_type' => $providerType,
                ],
                contextSnapshot: [
                    'order_public_id' => $assignment->order?->public_id,
                    'assignment_status' => $assignment->status,
                    'eta_minutes' => $assignment->eta_minutes,
                ],
            );

            return $assignment;
        });
    }

    public function advance(DeliveryAssignment $assignment, array $payload = []): DeliveryAssignment
    {
        $assignment->loadMissing('order', 'rider', 'trackingEvents');

        $nextStatus = $this->resolveNextStatus(
            currentStatus: (string) $assignment->status,
            explicitTarget: $payload['to_status'] ?? null,
        );

        return DB::transaction(function () use ($assignment, $payload, $nextStatus): DeliveryAssignment {
            $assignment = $assignment->fresh(['order', 'rider', 'trackingEvents']);
            $order = Order::query()->findOrFail($assignment->order_id);

            $update = [
                'status' => $nextStatus,
                'eta_minutes' => $this->etaFor($nextStatus),
            ];

            if ($nextStatus === 'rider_assigned') {
                $rider = $this->resolveRider(
                    providerType: (string) $assignment->provider_type,
                    riderPublicId: $payload['rider_public_id'] ?? $assignment->meta['preferred_rider_public_id'] ?? null,
                );

                $update['rider_id'] = $rider->id;
                $update['assigned_at'] = now();
            }

            if ($nextStatus === 'picked_up') {
                $update['picked_up_at'] = now();
            }

            if ($nextStatus === 'near_customer') {
                $update['near_customer_at'] = now();
            }

            if ($nextStatus === 'delivered') {
                $update['delivered_at'] = now();
            }

            $assignment->update($update);

            $order = $order->fresh(['items', 'fulfillment', 'statusHistory']);

            if ($order->status !== $nextStatus && in_array($nextStatus, $order->allowedTransitions(), true)) {
                $order = $this->orderTransitionService->transition(
                    order: $order,
                    toStatus: $nextStatus,
                    reason: 'Delivery simulation advanced.',
                    meta: [
                        'delivery_assignment_public_id' => $assignment->public_id,
                        'next_status' => $nextStatus,
                    ],
                    actorType: 'system',
                    actorId: null,
                );
            }

            $this->recordTrackingEvent(
                assignment: $assignment->fresh(),
                status: $nextStatus,
                note: $payload['note'] ?? 'Delivery simulation advanced.',
                payload: [
                    'rider_public_id' => $assignment->fresh()->rider?->public_id,
                ],
            );

            $assignment = $assignment->fresh([
                'order',
                'rider',
                'trackingEvents',
            ]);

            $this->emitOpsEventsForStatus($assignment, $nextStatus);

            app(AdminAuditLogger::class)->log(
                channel: 'simulation',
                action: 'rider.assignment.advanced',
                status: 'completed',
                actorType: 'system',
                actorId: null,
                entityType: 'delivery_assignment',
                entityPublicId: $assignment->public_id,
                entitySecondaryKey: $assignment->order?->order_code,
                summary: sprintf('Demo rider assignment advanced to %s.', $nextStatus),
                requestSnapshot: [
                    'to_status' => $nextStatus,
                    'note' => $payload['note'] ?? null,
                ],
                contextSnapshot: [
                    'order_public_id' => $assignment->order?->public_id,
                    'order_status' => $assignment->order?->status,
                    'assignment_status' => $assignment->status,
                    'eta_minutes' => $assignment->eta_minutes,
                    'rider_public_id' => $assignment->rider?->public_id,
                ],
            );

            return $assignment;
        });
    }

    protected function assertDeliveryOrder(Order $order): void
    {
        if ($order->fulfillment_type !== 'delivery') {
            throw ValidationException::withMessages([
                'order' => 'Rider simulation hanya dibenarkan untuk delivery order.',
            ]);
        }
    }

    protected function assertAssignableOrderStatus(Order $order): void
    {
        $allowed = (array) config('riders.simulation.assignable_order_statuses', []);

        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'order' => sprintf(
                    'Order status [%s] tidak sesuai untuk rider simulation.',
                    $order->status
                ),
            ]);
        }
    }

    protected function resolveNextStatus(string $currentStatus, ?string $explicitTarget = null): string
    {
        $flow = array_values((array) config('riders.simulation.flow', []));

        $index = array_search($currentStatus, $flow, true);

        if ($index === false) {
            throw ValidationException::withMessages([
                'status' => sprintf('Current assignment status [%s] tidak sah.', $currentStatus),
            ]);
        }

        if ($explicitTarget !== null) {
            $nextIndex = $index + 1;

            if (! isset($flow[$nextIndex]) || $flow[$nextIndex] !== $explicitTarget) {
                throw ValidationException::withMessages([
                    'to_status' => sprintf(
                        'Transition delivery assignment dari [%s] ke [%s] tidak dibenarkan.',
                        $currentStatus,
                        $explicitTarget
                    ),
                ]);
            }

            return $explicitTarget;
        }

        if (! isset($flow[$index + 1])) {
            throw ValidationException::withMessages([
                'status' => 'Delivery assignment ini sudah berada pada status akhir.',
            ]);
        }

        return $flow[$index + 1];
    }

    protected function resolveRider(string $providerType, ?string $riderPublicId = null): Rider
    {
        $this->ensureDemoRiderPool();

        if ($riderPublicId !== null) {
            $rider = Rider::query()
                ->where('public_id', $riderPublicId)
                ->where('status', 'active')
                ->first();

            if (! $rider) {
                throw ValidationException::withMessages([
                    'rider_public_id' => 'Rider yang dipilih tidak ditemui atau tidak aktif.',
                ]);
            }

            return $rider;
        }

        $rider = Rider::query()
            ->where('type', $providerType)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $rider) {
            throw ValidationException::withMessages([
                'provider_type' => sprintf('Tiada rider aktif untuk provider type [%s].', $providerType),
            ]);
        }

        return $rider;
    }

    protected function ensureDemoRiderPool(): void
    {
        foreach ((array) config('riders.simulation.default_rider_pool', []) as $seed) {
            Rider::query()->firstOrCreate(
                ['rider_code' => (string) $seed['rider_code']],
                [
                    'public_id' => (string) Str::uuid(),
                    'name' => (string) $seed['name'],
                    'phone' => null,
                    'type' => (string) $seed['type'],
                    'status' => 'active',
                    'vehicle_type' => $seed['vehicle_type'] ?? null,
                    'meta' => ['seeded_by' => 'rider_simulation_service'],
                    'is_demo' => true,
                ]
            );
        }
    }

    protected function etaFor(string $status): int
    {
        return (int) config("riders.simulation.eta_minutes.{$status}", 0);
    }

    protected function recordTrackingEvent(
        DeliveryAssignment $assignment,
        string $status,
        ?string $note = null,
        array $payload = [],
    ): void {
        $coords = (array) config("riders.simulation.coordinates.{$status}", []);

        $assignment->trackingEvents()->create([
            'status' => $status,
            'eta_minutes' => $this->etaFor($status),
            'simulated_latitude' => $coords['lat'] ?? null,
            'simulated_longitude' => $coords['lng'] ?? null,
            'note' => $note,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    protected function emitOpsEventsForStatus(DeliveryAssignment $assignment, string $status): void
    {
        $latestTracking = $assignment->trackingEvents()->latest('id')->first();

        if ($status === 'rider_assigned') {
            $this->opsWebhookEventService->emit(
                eventName: 'rider.assigned',
                context: [
                    'aggregate_type' => 'delivery',
                    'order_id' => $assignment->order_id,
                    'delivery_assignment_id' => $assignment->id,
                ],
                payload: [
                    'delivery_assignment_public_id' => (string) $assignment->public_id,
                    'order_public_id' => (string) $assignment->order?->public_id,
                    'rider_public_id' => $assignment->rider?->public_id,
                    'status' => $assignment->status,
                    'eta_minutes' => $assignment->eta_minutes,
                ],
                notes: 'Internal ops webhook simulation for rider assignment.',
            );

            return;
        }

        if (in_array($status, ['picked_up', 'near_customer'], true)) {
            $this->opsWebhookEventService->emit(
                eventName: 'rider.location_updated',
                context: [
                    'aggregate_type' => 'delivery',
                    'order_id' => $assignment->order_id,
                    'delivery_assignment_id' => $assignment->id,
                ],
                payload: [
                    'delivery_assignment_public_id' => (string) $assignment->public_id,
                    'order_public_id' => (string) $assignment->order?->public_id,
                    'rider_public_id' => $assignment->rider?->public_id,
                    'status' => $assignment->status,
                    'eta_minutes' => $assignment->eta_minutes,
                    'simulated_latitude' => $latestTracking?->simulated_latitude,
                    'simulated_longitude' => $latestTracking?->simulated_longitude,
                ],
                notes: 'Internal ops webhook simulation for rider location update.',
            );

            return;
        }

        if ($status === 'delivered') {
            $this->opsWebhookEventService->emit(
                eventName: 'order.delivered',
                context: [
                    'aggregate_type' => 'order',
                    'order_id' => $assignment->order_id,
                    'delivery_assignment_id' => $assignment->id,
                ],
                payload: [
                    'delivery_assignment_public_id' => (string) $assignment->public_id,
                    'order_public_id' => (string) $assignment->order?->public_id,
                    'rider_public_id' => $assignment->rider?->public_id,
                    'status' => $assignment->status,
                    'eta_minutes' => $assignment->eta_minutes,
                    'delivered_at' => optional($assignment->delivered_at)?->toIso8601String(),
                ],
                notes: 'Internal ops webhook simulation for delivered order.',
            );
        }
    }
}
