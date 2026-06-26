<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Ops\OpsWebhookEventService;
use App\Services\Orders\OrderTransitionService;
use App\Services\Payments\CreatePaymentRefundHookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateRefundService
{
    public function __construct(
        protected RefundEligibilityService $refundEligibilityService,
        protected OrderTransitionService $orderTransitionService,
        protected CreatePaymentRefundHookService $createPaymentRefundHookService,
        protected OpsWebhookEventService $opsWebhookEventService,
    ) {}

    public function handle(
        Order $order,
        array $payload,
        string $actorType = 'customer',
        ?int $actorId = null,
    ): Refund {
        $paymentIntent = $this->resolvePaymentIntent($order, $payload['payment_intent_id'] ?? null);
        $paymentTransaction = $this->resolvePaymentTransaction($paymentIntent, $payload['payment_transaction_id'] ?? null);

        $category = (string) $payload['category'];

        $evaluation = $this->refundEligibilityService->evaluate(
            $order,
            $category,
            $payload['requested_amount'] ?? null,
        );

        if (($evaluation['allowed'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'category' => $evaluation['reason'] ?? 'Refund request is not allowed.',
            ]);
        }

        return DB::transaction(function () use ($order, $payload, $actorType, $actorId, $paymentIntent, $paymentTransaction, $evaluation, $category): Refund {
            $refund = Refund::query()->create([
                'public_id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent?->id,
                'payment_transaction_id' => $paymentTransaction?->id,
                'category' => $category,
                'status' => 'requested',
                'resolution_type' => (string) ($evaluation['resolution_type'] ?? $this->defaultResolutionType($category)),
                'currency' => (string) ($paymentIntent?->currency ?? $order->currency),
                'requested_amount' => (int) $evaluation['requested_amount'],
                'reason' => $payload['reason'] ?? null,
                'policy_snapshot' => $evaluation['policy_snapshot'] ?? [],
                'context_snapshot' => array_merge([
                    'order_status_at_request' => $order->status,
                    'fulfillment_type' => $order->fulfillment_type,
                    'review_mode' => $evaluation['review_mode'] ?? 'review',
                ], (array) ($payload['context_snapshot'] ?? [])),
                'notes' => $payload['notes'] ?? null,
                'requested_by_type' => $actorType,
                'requested_by_id' => $actorId,
                'requested_at' => now(),
            ]);

            $this->recordEvent(
                refund: $refund,
                eventName: 'refund_requested',
                fromStatus: null,
                toStatus: 'requested',
                note: $payload['notes'] ?? null,
                payload: [
                    'category' => $category,
                    'requested_amount' => (int) $evaluation['requested_amount'],
                    'review_mode' => $evaluation['review_mode'] ?? 'review',
                ],
                actorType: $actorType,
                actorId: $actorId,
            );

            $this->emitRefundUpdated($refund, 'requested', [
                'review_mode' => $evaluation['review_mode'] ?? 'review',
            ]);

            app(AdminAuditLogger::class)->log(
                channel: 'simulation',
                action: 'refund.requested',
                status: 'completed',
                actorType: $actorType,
                actorId: $actorId !== null ? (string) $actorId : null,
                entityType: 'refund',
                entityPublicId: $refund->public_id,
                entitySecondaryKey: $order->order_code,
                summary: sprintf('Demo refund requested for order %s.', $order->order_code),
                requestSnapshot: [
                    'category' => $category,
                    'requested_amount' => (int) $evaluation['requested_amount'],
                    'review_mode' => $evaluation['review_mode'] ?? 'review',
                ],
                contextSnapshot: [
                    'order_public_id' => $order->public_id,
                    'order_status' => $order->status,
                    'refund_status' => $refund->status,
                    'demo_safe' => true,
                ],
            );

            if (($evaluation['review_mode'] ?? 'review') === 'auto') {
                return $this->processApprovedRefund(
                    refund: $refund,
                    payload: [
                        'approved_amount' => (int) $evaluation['requested_amount'],
                        'resolution_type' => (string) ($evaluation['resolution_type'] ?? $this->defaultResolutionType($category)),
                        'notes' => 'Auto-processed by refund policy.',
                    ],
                    actorType: 'system',
                    actorId: null,
                    paymentIntent: $paymentIntent,
                    paymentTransaction: $paymentTransaction,
                );
            }

            return $refund->fresh([
                'order',
                'paymentIntent',
                'paymentTransaction',
                'events',
            ]);
        });
    }

    public function processApprovedRefund(
        Refund $refund,
        array $payload,
        string $actorType = 'admin',
        ?int $actorId = null,
        ?PaymentIntent $paymentIntent = null,
        ?PaymentTransaction $paymentTransaction = null,
    ): Refund {
        if (in_array($refund->status, ['processed', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'refund' => 'Refund ini sudah berada pada status akhir.',
            ]);
        }

        $approvedAmount = (int) ($payload['approved_amount'] ?? $refund->requested_amount);

        if ($approvedAmount < 1) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Approved amount mesti lebih besar daripada sifar.',
            ]);
        }

        if ($approvedAmount > (int) $refund->requested_amount) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Approved amount tidak boleh melebihi requested amount.',
            ]);
        }

        $resolutionType = (string) ($payload['resolution_type'] ?? $refund->resolution_type ?? $this->defaultResolutionType($refund->category));

        if (! in_array($resolutionType, (array) config('refunds.resolution_types', []), true)) {
            throw ValidationException::withMessages([
                'resolution_type' => 'Resolution type tidak sah.',
            ]);
        }

        $order = $refund->order()->firstOrFail();
        $paymentIntent ??= $refund->paymentIntent;
        $paymentTransaction ??= $refund->paymentTransaction;

        return DB::transaction(function () use ($refund, $payload, $actorType, $actorId, $approvedAmount, $resolutionType, $order, $paymentIntent, $paymentTransaction): Refund {
            $refund->update([
                'status' => 'approved',
                'approved_amount' => $approvedAmount,
                'resolution_type' => $resolutionType,
                'reviewed_at' => now(),
                'notes' => $this->mergeNotes($refund->notes, $payload['notes'] ?? null),
            ]);

            $this->recordEvent(
                refund: $refund,
                eventName: 'refund_approved',
                fromStatus: 'requested',
                toStatus: 'approved',
                note: $payload['notes'] ?? null,
                payload: [
                    'approved_amount' => $approvedAmount,
                    'resolution_type' => $resolutionType,
                ],
                actorType: $actorType,
                actorId: $actorId,
            );

            $this->emitRefundUpdated($refund, 'processed', [
                'resolution_type' => $resolutionType,
                'resolved_amount' => $approvedAmount,
                'order_status' => $order->status,
            ]);

            $updatedOrder = $this->applyOrderRefundTransition(
                $order,
                $refund,
                $resolutionType,
                $actorType,
                $actorId,
            );

            $refund->update([
                'status' => 'processed',
                'resolved_amount' => $approvedAmount,
                'resolved_at' => now(),
                'context_snapshot' => array_merge((array) ($refund->context_snapshot ?? []), [
                    'order_status_after_processing' => $updatedOrder->status,
                ]),
            ]);

            $this->recordEvent(
                refund: $refund,
                eventName: 'refund_processed',
                fromStatus: 'approved',
                toStatus: 'processed',
                note: 'Refund processed in demo-safe mode.',
                payload: [
                    'resolved_amount' => $approvedAmount,
                    'resolution_type' => $resolutionType,
                    'order_status' => $order->status,
                ],
                actorType: $actorType,
                actorId: $actorId,
            );

            $this->maybeCreatePlaceholderRefundHook(
                refund: $refund,
                paymentIntent: $paymentIntent,
                paymentTransaction: $paymentTransaction,
                approvedAmount: $approvedAmount,
                resolutionType: $resolutionType,
            );

            app(AdminAuditLogger::class)->log(
                channel: 'simulation',
                action: 'refund.processed',
                status: 'completed',
                actorType: $actorType,
                actorId: $actorId !== null ? (string) $actorId : null,
                entityType: 'refund',
                entityPublicId: $refund->public_id,
                entitySecondaryKey: $order->order_code,
                summary: sprintf('Demo refund processed for order %s.', $order->order_code),
                requestSnapshot: [
                    'approved_amount' => $approvedAmount,
                    'resolution_type' => $resolutionType,
                ],
                contextSnapshot: [
                    'order_public_id' => $order->public_id,
                    'order_status_after_processing' => $updatedOrder->status,
                    'refund_status' => 'processed',
                    'demo_safe' => true,
                ],
            );

            return $refund->fresh([
                'order',
                'paymentIntent',
                'paymentTransaction',
                'events',
            ]);
        });
    }

    protected function resolvePaymentIntent(Order $order, ?int $paymentIntentId = null): ?PaymentIntent
    {
        if ($paymentIntentId === null) {
            return $order->paymentIntents()->latest('id')->first();
        }

        $intent = PaymentIntent::query()
            ->where('order_id', $order->id)
            ->find($paymentIntentId);

        if (! $intent) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent tidak ditemui untuk order ini.',
            ]);
        }

        return $intent;
    }

    protected function resolvePaymentTransaction(?PaymentIntent $paymentIntent, ?int $paymentTransactionId = null): ?PaymentTransaction
    {
        if ($paymentIntent === null) {
            if ($paymentTransactionId !== null) {
                throw ValidationException::withMessages([
                    'payment_transaction_id' => 'Payment transaction memerlukan payment intent yang sah.',
                ]);
            }

            return null;
        }

        if ($paymentTransactionId === null) {
            return $paymentIntent->transactions()->latest('id')->first();
        }

        $transaction = PaymentTransaction::query()
            ->where('payment_intent_id', $paymentIntent->id)
            ->find($paymentTransactionId);

        if (! $transaction) {
            throw ValidationException::withMessages([
                'payment_transaction_id' => 'Payment transaction tidak ditemui untuk payment intent ini.',
            ]);
        }

        return $transaction;
    }

    protected function applyOrderRefundTransition(
        Order $order,
        Refund $refund,
        string $resolutionType,
        string $actorType,
        ?int $actorId,
    ): Order {
        $targetStatus = $this->resolveTargetOrderStatus($refund, $resolutionType);

        $freshOrder = $order->fresh(['items', 'fulfillment', 'statusHistory']);

        if ($targetStatus === null || $freshOrder->status === $targetStatus) {
            return $freshOrder;
        }

        if (in_array($targetStatus, $freshOrder->allowedTransitions(), true)) {
            return $this->orderTransitionService->transition(
                order: $freshOrder,
                toStatus: $targetStatus,
                reason: 'Refund processed.',
                meta: [
                    'refund_public_id' => $refund->public_id,
                    'refund_category' => $refund->category,
                    'resolution_type' => $resolutionType,
                ],
                actorType: $actorType,
                actorId: $actorId,
            );
        }

        if ($targetStatus !== 'cancelled' && in_array('refund_pending', $freshOrder->allowedTransitions(), true)) {
            $freshOrder = $this->orderTransitionService->transition(
                order: $freshOrder,
                toStatus: 'refund_pending',
                reason: 'Refund processing started.',
                meta: [
                    'refund_public_id' => $refund->public_id,
                    'refund_category' => $refund->category,
                    'resolution_type' => $resolutionType,
                    'next_target_status' => $targetStatus,
                ],
                actorType: $actorType,
                actorId: $actorId,
            );

            if (in_array($targetStatus, $freshOrder->allowedTransitions(), true)) {
                return $this->orderTransitionService->transition(
                    order: $freshOrder,
                    toStatus: $targetStatus,
                    reason: 'Refund processing completed.',
                    meta: [
                        'refund_public_id' => $refund->public_id,
                        'refund_category' => $refund->category,
                        'resolution_type' => $resolutionType,
                    ],
                    actorType: $actorType,
                    actorId: $actorId,
                );
            }
        }

        return $freshOrder;
    }

    protected function resolveTargetOrderStatus(Refund $refund, string $resolutionType): ?string
    {
        $policy = (array) ($refund->policy_snapshot ?? []);
        $stageKey = (string) ($policy['stage_key'] ?? '');

        if ($stageKey === '') {
            return null;
        }

        $transitionConfig = (array) config("refunds.stages.{$stageKey}.order_transition", []);

        return match ($resolutionType) {
            'full_refund' => $transitionConfig['processed_full'] ?? null,
            'store_credit' => $transitionConfig['store_credit'] ?? null,
            default => $transitionConfig['processed_partial'] ?? null,
        };
    }

    protected function maybeCreatePlaceholderRefundHook(
        Refund $refund,
        ?PaymentIntent $paymentIntent,
        ?PaymentTransaction $paymentTransaction,
        int $approvedAmount,
        string $resolutionType,
    ): void {
        if ($paymentIntent === null) {
            return;
        }

        if ((bool) config('refunds.payment_placeholder.create_refund_hook_on_processed', false) !== true) {
            return;
        }

        $hookType = match ($resolutionType) {
            'full_refund' => 'refund_requested',
            'store_credit' => 'store_credit_requested',
            default => 'partial_refund_requested',
        };

        $this->createPaymentRefundHookService->handle($paymentIntent, [
            'hook_type' => $hookType,
            'payment_transaction_id' => $paymentTransaction?->id,
            'amount' => $approvedAmount,
            'currency' => $refund->currency,
            'reason' => $refund->reason,
            'payload' => [
                'refund_public_id' => (string) $refund->public_id,
                'refund_category' => (string) $refund->category,
                'resolution_type' => $resolutionType,
                'demo_safe' => true,
            ],
            'notes' => 'Generated by refund business flow. Demo-safe placeholder only.',
        ]);
    }

    protected function recordEvent(
        Refund $refund,
        string $eventName,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $note,
        array $payload,
        string $actorType,
        ?int $actorId,
    ): void {
        $refund->events()->create([
            'event_name' => $eventName,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'payload' => $payload,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'occurred_at' => now(),
        ]);
    }

    protected function defaultResolutionType(string $category): string
    {
        return match ($category) {
            'full_refund' => 'full_refund',
            'store_credit' => 'store_credit',
            default => 'partial_refund',
        };
    }

    protected function mergeNotes(?string $existing, ?string $incoming): ?string
    {
        $existing = trim((string) ($existing ?? ''));
        $incoming = trim((string) ($incoming ?? ''));

        if ($existing === '') {
            return $incoming !== '' ? $incoming : null;
        }

        if ($incoming === '') {
            return $existing;
        }

        return $existing.PHP_EOL.PHP_EOL.$incoming;
    }

    protected function emitRefundUpdated(Refund $refund, string $phase, array $payload = []): void
    {
        $this->opsWebhookEventService->emit(
            eventName: 'refund.updated',
            context: [
                'aggregate_type' => 'refund',
                'order_id' => $refund->order_id,
                'refund_id' => $refund->id,
                'payment_intent_id' => $refund->payment_intent_id,
            ],
            payload: array_merge([
                'phase' => $phase,
                'refund_public_id' => (string) $refund->public_id,
                'refund_status' => (string) $refund->status,
                'resolution_type' => $refund->resolution_type,
                'requested_amount' => (int) $refund->requested_amount,
                'resolved_amount' => $refund->resolved_amount,
            ], $payload),
            notes: 'Internal ops webhook simulation for refund.',
        );
    }
}
