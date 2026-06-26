<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Models\Refund;
use App\Services\Ops\OpsWebhookEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewRefundService
{
    public function __construct(
        protected CreateRefundService $createRefundService,
        protected OpsWebhookEventService $opsWebhookEventService,
    ) {}

    public function handle(
        Refund $refund,
        array $payload,
        string $actorType = 'admin',
        ?int $actorId = null,
    ): Refund {
        if (in_array($refund->status, ['processed', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'refund' => 'Refund ini sudah berada pada status akhir.',
            ]);
        }

        $decision = (string) $payload['decision'];

        $this->emitRefundUpdated($refund, 'reviewed', [
            'decision' => $decision,
            'approved_amount' => $payload['approved_amount'] ?? $refund->requested_amount,
            'resolution_type' => $payload['resolution_type'] ?? $refund->resolution_type,
        ]);

        return match ($decision) {
            'under_review' => $this->markUnderReview($refund, $payload, $actorType, $actorId),
            'approve' => $this->approve($refund, $payload, $actorType, $actorId),
            'reject' => $this->reject($refund, $payload, $actorType, $actorId),
            default => throw ValidationException::withMessages([
                'decision' => 'Decision tidak sah.',
            ]),
        };
    }

    protected function markUnderReview(
        Refund $refund,
        array $payload,
        string $actorType,
        ?int $actorId,
    ): Refund {
        if ($refund->status === 'under_review') {
            throw ValidationException::withMessages([
                'decision' => 'Refund sudah berada pada status under_review.',
            ]);
        }

        return DB::transaction(function () use ($refund, $payload, $actorType, $actorId): Refund {
            $fromStatus = $refund->status;

            $refund->update([
                'status' => 'under_review',
                'reviewed_at' => now(),
                'notes' => $this->mergeNotes($refund->notes, $payload['notes'] ?? null),
            ]);

            $refund->events()->create([
                'event_name' => 'refund_under_review',
                'from_status' => $fromStatus,
                'to_status' => 'under_review',
                'note' => $payload['notes'] ?? null,
                'payload' => [],
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);

            $this->emitRefundUpdated($refund, 'under_review', [
                'approved_amount' => $payload['approved_amount'] ?? $refund->requested_amount,
                'resolution_type' => $payload['resolution_type'] ?? $refund->resolution_type,
                'order_status' => $refund->order->status,
            ]);

            return $refund->fresh([
                'order',
                'paymentIntent',
                'paymentTransaction',
                'events',
            ]);
        });
    }

    protected function approve(
        Refund $refund,
        array $payload,
        string $actorType,
        ?int $actorId,
    ): Refund {
        return $this->createRefundService->processApprovedRefund(
            refund: $refund,
            payload: [
                'approved_amount' => $payload['approved_amount'] ?? $refund->requested_amount,
                'resolution_type' => $payload['resolution_type'] ?? $refund->resolution_type,
                'notes' => $payload['notes'] ?? null,
            ],
            actorType: $actorType,
            actorId: $actorId,
        );
    }

    protected function reject(
        Refund $refund,
        array $payload,
        string $actorType,
        ?int $actorId,
    ): Refund {
        return DB::transaction(function () use ($refund, $payload, $actorType, $actorId): Refund {
            $fromStatus = $refund->status;

            $refund->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'resolved_at' => now(),
                'notes' => $this->mergeNotes($refund->notes, $payload['notes'] ?? null),
            ]);

            $refund->events()->create([
                'event_name' => 'refund_rejected',
                'from_status' => $fromStatus,
                'to_status' => 'rejected',
                'note' => $payload['notes'] ?? null,
                'payload' => [],
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);

            return $refund->fresh([
                'order',
                'paymentIntent',
                'paymentTransaction',
                'events',
            ]);
        });
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

    protected function emitRefundUpdated(Refund $refund, string $phase, array $extraPayload = []): void
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
            ], $extraPayload),
            notes: 'Internal ops webhook simulation for refund.',
        );
    }
}
