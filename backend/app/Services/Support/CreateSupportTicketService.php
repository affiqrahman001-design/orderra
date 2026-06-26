<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Refund;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateSupportTicketService
{
    public function handle(
        array $payload,
        string $actorType = 'customer',
        ?int $actorId = null,
    ): SupportTicket {
        $order = $this->resolveOrder($payload['order_id'] ?? null);
        $refund = $this->resolveRefund($payload['refund_id'] ?? null);
        $paymentIntent = $this->resolvePaymentIntent($payload['payment_intent_id'] ?? null);
        $deliveryAssignment = $this->resolveDeliveryAssignment($payload['delivery_assignment_id'] ?? null);

        $this->assertLinkageConsistency($order, $refund, $paymentIntent, $deliveryAssignment);

        return DB::transaction(function () use (
            $payload,
            $actorType,
            $actorId,
            $order,
            $refund,
            $paymentIntent,
            $deliveryAssignment
        ): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'public_id' => (string) Str::uuid(),
                'order_id' => $order?->id,
                'refund_id' => $refund?->id,
                'payment_intent_id' => $paymentIntent?->id,
                'delivery_assignment_id' => $deliveryAssignment?->id,
                'category' => (string) $payload['category'],
                'status' => (string) config('support.default_status', 'open'),
                'subject' => (string) $payload['subject'],
                'description' => (string) $payload['description'],
                'contact_snapshot' => $payload['contact_snapshot'] ?? [],
                'meta' => array_merge((array) ($payload['meta'] ?? []), [
                    'demo_safe' => true,
                ]),
                'opened_at' => now(),
            ]);

            $ticket->events()->create([
                'event_name' => 'ticket_opened',
                'from_status' => null,
                'to_status' => $ticket->status,
                'note' => 'Support ticket created.',
                'payload' => [
                    'category' => $ticket->category,
                    'delivery_assignment_id' => $deliveryAssignment?->public_id,
                ],
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);

            return $ticket->fresh([
                'order',
                'refund',
                'paymentIntent',
                'deliveryAssignment',
                'events',
            ]);
        });
    }

    protected function resolveOrder(mixed $orderReference): ?Order
    {
        $reference = $this->normalizeReference($orderReference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $order = Order::query()->find((int) $reference);

            if ($order) {
                return $order;
            }
        }

        $order = Order::query()
            ->where('public_id', $reference)
            ->orWhere('order_code', $reference)
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'order_id' => 'Order tidak dijumpai.',
            ]);
        }

        return $order;
    }

    protected function resolveRefund(mixed $refundReference): ?Refund
    {
        $reference = $this->normalizeReference($refundReference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $refund = Refund::query()->find((int) $reference);

            if ($refund) {
                return $refund;
            }
        }

        $refund = Refund::query()->where('public_id', $reference)->first();

        if (! $refund) {
            throw ValidationException::withMessages([
                'refund_id' => 'Refund tidak dijumpai.',
            ]);
        }

        return $refund;
    }

    protected function resolvePaymentIntent(mixed $paymentIntentReference): ?PaymentIntent
    {
        $reference = $this->normalizeReference($paymentIntentReference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $intent = PaymentIntent::query()->find((int) $reference);

            if ($intent) {
                return $intent;
            }
        }

        $intent = PaymentIntent::query()
            ->where('public_id', $reference)
            ->orWhere('intent_code', $reference)
            ->first();

        if (! $intent) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent tidak dijumpai.',
            ]);
        }

        return $intent;
    }

    protected function resolveDeliveryAssignment(mixed $assignmentReference): ?DeliveryAssignment
    {
        $reference = $this->normalizeReference($assignmentReference);

        if ($reference === null) {
            return null;
        }

        if (ctype_digit($reference)) {
            $assignment = DeliveryAssignment::query()->find((int) $reference);

            if ($assignment) {
                return $assignment;
            }
        }

        $assignment = DeliveryAssignment::query()->where('public_id', $reference)->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'delivery_assignment_id' => 'Delivery assignment tidak dijumpai.',
            ]);
        }

        return $assignment;
    }

    protected function assertLinkageConsistency(
        ?Order $order,
        ?Refund $refund,
        ?PaymentIntent $paymentIntent,
        ?DeliveryAssignment $deliveryAssignment,
    ): void {
        if ($order && $refund && (int) $refund->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'refund_id' => 'Refund yang dipilih tidak berkaitan dengan order ini.',
            ]);
        }

        if ($order && $paymentIntent && (int) $paymentIntent->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent yang dipilih tidak berkaitan dengan order ini.',
            ]);
        }

        if ($order && $deliveryAssignment && (int) $deliveryAssignment->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'delivery_assignment_id' => 'Delivery assignment yang dipilih tidak berkaitan dengan order ini.',
            ]);
        }

        if ($refund && $paymentIntent && $refund->payment_intent_id !== null && (int) $refund->payment_intent_id !== (int) $paymentIntent->id) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent yang dipilih tidak sepadan dengan refund ini.',
            ]);
        }
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
