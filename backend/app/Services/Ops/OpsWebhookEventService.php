<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Models\DeliveryAssignment;
use App\Models\OpsWebhookEvent;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Refund;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OpsWebhookEventService
{
    public function emit(
        string $eventName,
        array $context = [],
        array $payload = [],
        array $headers = [],
        ?string $notes = null,
        ?string $status = null,
    ): OpsWebhookEvent {
        $this->assertAllowedEvent($eventName);

        $resolvedContext = $this->resolveContext($context);

        $event = OpsWebhookEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'event_name' => $eventName,
            'aggregate_type' => $this->resolveAggregateType($eventName, $resolvedContext),
            'status' => $status ?? (string) config('ops.webhooks.default_status', 'processed'),
            'order_id' => $resolvedContext['order_id'] ?? null,
            'refund_id' => $resolvedContext['refund_id'] ?? null,
            'payment_intent_id' => $resolvedContext['payment_intent_id'] ?? null,
            'delivery_assignment_id' => $resolvedContext['delivery_assignment_id'] ?? null,
            'payload' => array_merge($payload, ['demo_safe' => true]),
            'headers' => $headers,
            'notes' => $notes,
            'generated_at' => now(),
            'replay_count' => 0,
        ]);

        app(AdminAuditLogger::class)->log(
            channel: 'simulation',
            action: 'ops.webhook.emit',
            status: 'completed',
            actorType: 'system',
            actorId: null,
            entityType: 'ops_webhook',
            entityPublicId: $event->public_id,
            entitySecondaryKey: $eventName,
            summary: sprintf('Demo webhook [%s] emitted.', $eventName),
            requestSnapshot: [
                'event_name' => $eventName,
                'headers' => $headers,
            ],
            contextSnapshot: [
                'aggregate_type' => $event->aggregate_type,
                'order_id' => $event->order_id,
                'refund_id' => $event->refund_id,
                'payment_intent_id' => $event->payment_intent_id,
                'delivery_assignment_id' => $event->delivery_assignment_id,
            ],
        );

        return $event->fresh([
            'order',
            'refund',
            'paymentIntent',
            'deliveryAssignment',
        ]);
    }

    protected function assertAllowedEvent(string $eventName): void
    {
        if (! in_array($eventName, (array) config('ops.webhooks.allowed_events', []), true)) {
            throw ValidationException::withMessages([
                'event_name' => sprintf('Event [%s] tidak dibenarkan.', $eventName),
            ]);
        }
    }

    protected function resolveAggregateType(string $eventName, array $context): string
    {
        if (isset($context['aggregate_type']) && is_string($context['aggregate_type'])) {
            return $context['aggregate_type'];
        }

        $map = (array) config('ops.webhooks.event_aggregate_map', []);

        if (isset($map[$eventName])) {
            return (string) $map[$eventName];
        }

        if (! empty($context['delivery_assignment_id'])) {
            return 'delivery';
        }

        if (! empty($context['refund_id'])) {
            return 'refund';
        }

        if (! empty($context['payment_intent_id'])) {
            return 'payment';
        }

        return 'order';
    }

    private function resolveContext(array $context): array
    {
        $resolved = $context;

        $resolved['order_id'] = $this->resolveOrderId($context['order_id'] ?? null);
        $resolved['refund_id'] = $this->resolveRefundId($context['refund_id'] ?? null);
        $resolved['payment_intent_id'] = $this->resolvePaymentIntentId($context['payment_intent_id'] ?? null);
        $resolved['delivery_assignment_id'] = $this->resolveDeliveryAssignmentId($context['delivery_assignment_id'] ?? null);

        if (empty($resolved['order_id']) && ! empty($resolved['delivery_assignment_id'])) {
            $assignment = DeliveryAssignment::query()->find((int) $resolved['delivery_assignment_id']);
            $resolved['order_id'] = $assignment?->order_id;
        }

        if (empty($resolved['order_id']) && ! empty($resolved['refund_id'])) {
            $refund = Refund::query()->find((int) $resolved['refund_id']);
            $resolved['order_id'] = $refund?->order_id;
        }

        return $resolved;
    }

    private function resolveOrderId(mixed $reference): ?int
    {
        $reference = $this->normalizeReference($reference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $order = Order::query()->find((int) $reference);
            if ($order) {
                return $order->id;
            }
        }

        $order = Order::query()
            ->where('public_id', $reference)
            ->orWhere('order_code', $reference)
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['order_id' => 'Order tidak dijumpai.']);
        }

        return $order->id;
    }

    private function resolveRefundId(mixed $reference): ?int
    {
        $reference = $this->normalizeReference($reference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $refund = Refund::query()->find((int) $reference);
            if ($refund) {
                return $refund->id;
            }
        }

        $refund = Refund::query()->where('public_id', $reference)->first();

        if (! $refund) {
            throw ValidationException::withMessages(['refund_id' => 'Refund tidak dijumpai.']);
        }

        return $refund->id;
    }

    private function resolvePaymentIntentId(mixed $reference): ?int
    {
        $reference = $this->normalizeReference($reference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $intent = PaymentIntent::query()->find((int) $reference);
            if ($intent) {
                return $intent->id;
            }
        }

        $intent = PaymentIntent::query()
            ->where('public_id', $reference)
            ->orWhere('intent_code', $reference)
            ->first();

        if (! $intent) {
            throw ValidationException::withMessages(['payment_intent_id' => 'Payment intent tidak dijumpai.']);
        }

        return $intent->id;
    }

    private function resolveDeliveryAssignmentId(mixed $reference): ?int
    {
        $reference = $this->normalizeReference($reference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $assignment = DeliveryAssignment::query()->find((int) $reference);
            if ($assignment) {
                return $assignment->id;
            }
        }

        $assignment = DeliveryAssignment::query()->where('public_id', $reference)->first();

        if (! $assignment) {
            throw ValidationException::withMessages(['delivery_assignment_id' => 'Delivery assignment tidak dijumpai.']);
        }

        return $assignment->id;
    }

    private function normalizeReference(mixed $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $value = trim((string) $reference);

        return $value !== '' ? $value : null;
    }
}
