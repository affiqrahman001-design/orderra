<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Models\PaymentIntent;
use App\Models\PaymentTransaction;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPaymentLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PaymentIntent::query()
            ->with(['order'])
            ->withCount(['attempts', 'transactions', 'refunds', 'supportTickets']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($methodCode = $request->string('method_code')->toString()) {
            $query->where('method_code', $methodCode);
        }

        if ($providerCode = $request->string('provider_code')->toString()) {
            $query->where('provider_code', $providerCode);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('intent_code', 'like', '%'.$search.'%')
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
                fn (PaymentIntent $paymentIntent) => $this->mapIntentSummary($paymentIntent)
            )->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function show(PaymentIntent $paymentIntent): JsonResponse
    {
        $paymentIntent->load([
            'order',
            'attempts',
            'transactions',
            'refunds',
            'supportTickets',
        ]);

        return response()->json([
            'data' => [
                'id' => $paymentIntent->public_id,
                'intent_code' => $paymentIntent->intent_code,
                'status' => $this->enumValue($paymentIntent->status),
                'method_code' => $this->enumValue($paymentIntent->method_code),
                'provider_code' => $this->enumValue($paymentIntent->provider_code),
                'country_code' => $paymentIntent->country_code,
                'currency' => $paymentIntent->currency,
                'amount' => $this->toMoney((int) $paymentIntent->amount),
                'branch_code' => $paymentIntent->branch_code,
                'order' => $paymentIntent->order ? [
                    'id' => $paymentIntent->order->public_id,
                    'order_code' => $paymentIntent->order->order_code,
                    'status' => $paymentIntent->order->status,
                    'fulfillment_type' => $paymentIntent->order->fulfillment_type,
                ] : null,
                'simulation_context' => $paymentIntent->simulation_context ?? [],
                'provider_context' => $paymentIntent->provider_context ?? [],
                'attempts' => $paymentIntent->attempts->map(
                    fn (PaymentAttempt $attempt) => $this->mapAttemptSummary($attempt)
                )->values(),
                'transactions' => $paymentIntent->transactions->map(
                    fn (PaymentTransaction $transaction) => $this->mapTransactionSummary($transaction)
                )->values(),
                'refunds' => $paymentIntent->refunds->map(fn ($refund) => [
                    'id' => $refund->public_id,
                    'category' => $refund->category,
                    'status' => $refund->status,
                    'resolution_type' => $refund->resolution_type,
                    'requested_amount' => $this->toMoney((int) $refund->requested_amount),
                    'resolved_amount' => $refund->resolved_amount !== null
                      ? $this->toMoney((int) $refund->resolved_amount)
                      : null,
                    'requested_at' => optional($refund->requested_at)?->toIso8601String(),
                    'resolved_at' => optional($refund->resolved_at)?->toIso8601String(),
                ])->values(),
                'support_tickets' => $paymentIntent->supportTickets->map(fn ($ticket) => [
                    'id' => $ticket->public_id,
                    'category' => $ticket->category,
                    'status' => $ticket->status,
                    'subject' => $ticket->subject,
                    'opened_at' => optional($ticket->opened_at)?->toIso8601String(),
                ])->values(),
                'expires_at' => optional($paymentIntent->expires_at)?->toIso8601String(),
                'last_attempted_at' => optional($paymentIntent->last_attempted_at)?->toIso8601String(),
                'authorized_at' => optional($paymentIntent->authorized_at)?->toIso8601String(),
                'succeeded_at' => optional($paymentIntent->succeeded_at)?->toIso8601String(),
                'failed_at' => optional($paymentIntent->failed_at)?->toIso8601String(),
                'cancelled_at' => optional($paymentIntent->cancelled_at)?->toIso8601String(),
                'created_at' => optional($paymentIntent->created_at)?->toIso8601String(),
                'updated_at' => optional($paymentIntent->updated_at)?->toIso8601String(),
            ],
        ]);
    }

    public function attempts(Request $request): JsonResponse
    {
        $query = PaymentAttempt::query()
            ->with(['paymentIntent.order']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($methodCode = $request->string('method_code')->toString()) {
            $query->where('method_code', $methodCode);
        }

        if ($providerCode = $request->string('provider_code')->toString()) {
            $query->where('provider_code', $providerCode);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('provider_reference', 'like', '%'.$search.'%')
                    ->orWhere('error_code', 'like', '%'.$search.'%')
                    ->orWhereHas('paymentIntent', function ($intentQuery) use ($search): void {
                        $intentQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('intent_code', 'like', '%'.$search.'%')
                            ->orWhereHas('order', function ($orderQuery) use ($search): void {
                                $orderQuery
                                    ->where('public_id', 'like', '%'.$search.'%')
                                    ->orWhere('order_code', 'like', '%'.$search.'%');
                            });
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (PaymentAttempt $attempt) => $this->mapAttemptSummary($attempt)
            )->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function showAttempt(PaymentAttempt $paymentAttempt): JsonResponse
    {
        $paymentAttempt->load(['paymentIntent.order']);

        return response()->json([
            'data' => array_merge($this->mapAttemptSummary($paymentAttempt), [
                'request_payload' => $paymentAttempt->request_payload ?? [],
                'response_payload' => $paymentAttempt->response_payload ?? [],
                'meta' => $paymentAttempt->meta ?? [],
            ]),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()
            ->with(['paymentIntent.order', 'paymentAttempt']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($transactionType = $request->string('transaction_type')->toString()) {
            $query->where('transaction_type', $transactionType);
        }

        if ($direction = $request->string('direction')->toString()) {
            $query->where('direction', $direction);
        }

        if ($methodCode = $request->string('method_code')->toString()) {
            $query->where('method_code', $methodCode);
        }

        if ($providerCode = $request->string('provider_code')->toString()) {
            $query->where('provider_code', $providerCode);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('provider_reference', 'like', '%'.$search.'%')
                    ->orWhere('external_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('paymentIntent', function ($intentQuery) use ($search): void {
                        $intentQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('intent_code', 'like', '%'.$search.'%')
                            ->orWhereHas('order', function ($orderQuery) use ($search): void {
                                $orderQuery
                                    ->where('public_id', 'like', '%'.$search.'%')
                                    ->orWhere('order_code', 'like', '%'.$search.'%');
                            });
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (PaymentTransaction $transaction) => $this->mapTransactionSummary($transaction)
            )->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function showTransaction(PaymentTransaction $paymentTransaction): JsonResponse
    {
        $paymentTransaction->load(['paymentIntent.order', 'paymentAttempt', 'refunds']);

        return response()->json([
            'data' => array_merge($this->mapTransactionSummary($paymentTransaction), [
                'payload' => $paymentTransaction->payload ?? [],
                'refunds' => $paymentTransaction->refunds->map(fn ($refund) => [
                    'id' => $refund->public_id,
                    'status' => $refund->status,
                    'resolution_type' => $refund->resolution_type,
                    'requested_amount' => $this->toMoney((int) $refund->requested_amount),
                    'resolved_amount' => $refund->resolved_amount !== null
                      ? $this->toMoney((int) $refund->resolved_amount)
                      : null,
                ])->values(),
            ]),
        ]);
    }

    private function mapIntentSummary(PaymentIntent $paymentIntent): array
    {
        return [
            'id' => $paymentIntent->public_id,
            'intent_code' => $paymentIntent->intent_code,
            'status' => $this->enumValue($paymentIntent->status),
            'method_code' => $this->enumValue($paymentIntent->method_code),
            'provider_code' => $this->enumValue($paymentIntent->provider_code),
            'currency' => $paymentIntent->currency,
            'amount' => $this->toMoney((int) $paymentIntent->amount),
            'attempts_count' => (int) ($paymentIntent->attempts_count ?? 0),
            'transactions_count' => (int) ($paymentIntent->transactions_count ?? 0),
            'refunds_count' => (int) ($paymentIntent->refunds_count ?? 0),
            'support_tickets_count' => (int) ($paymentIntent->support_tickets_count ?? 0),
            'order' => $paymentIntent->order ? [
                'id' => $paymentIntent->order->public_id,
                'order_code' => $paymentIntent->order->order_code,
                'status' => $paymentIntent->order->status,
            ] : null,
            'created_at' => optional($paymentIntent->created_at)?->toIso8601String(),
            'last_attempted_at' => optional($paymentIntent->last_attempted_at)?->toIso8601String(),
        ];
    }

    private function mapAttemptSummary(PaymentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $this->enumValue($attempt->status),
            'method_code' => $this->enumValue($attempt->method_code),
            'provider_code' => $this->enumValue($attempt->provider_code),
            'amount' => $this->toMoney((int) $attempt->amount),
            'simulation_outcome' => $attempt->simulation_outcome,
            'provider_reference' => $attempt->provider_reference,
            'error_code' => $attempt->error_code,
            'error_message' => $attempt->error_message,
            'payment_intent' => $attempt->paymentIntent ? [
                'id' => $attempt->paymentIntent->public_id,
                'intent_code' => $attempt->paymentIntent->intent_code,
                'status' => $this->enumValue($attempt->paymentIntent->status),
                'order' => $attempt->paymentIntent->order ? [
                    'id' => $attempt->paymentIntent->order->public_id,
                    'order_code' => $attempt->paymentIntent->order->order_code,
                    'status' => $attempt->paymentIntent->order->status,
                ] : null,
            ] : null,
            'initiated_at' => optional($attempt->initiated_at)?->toIso8601String(),
            'processed_at' => optional($attempt->processed_at)?->toIso8601String(),
            'created_at' => optional($attempt->created_at)?->toIso8601String(),
        ];
    }

    private function mapTransactionSummary(PaymentTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_type' => $transaction->transaction_type,
            'direction' => $transaction->direction,
            'status' => $this->enumValue($transaction->status),
            'method_code' => $this->enumValue($transaction->method_code),
            'provider_code' => $this->enumValue($transaction->provider_code),
            'currency' => $transaction->currency,
            'amount' => $this->toMoney((int) $transaction->amount),
            'provider_reference' => $transaction->provider_reference,
            'external_reference' => $transaction->external_reference,
            'payment_attempt_id' => $transaction->payment_attempt_id,
            'payment_intent' => $transaction->paymentIntent ? [
                'id' => $transaction->paymentIntent->public_id,
                'intent_code' => $transaction->paymentIntent->intent_code,
                'status' => $this->enumValue($transaction->paymentIntent->status),
                'order' => $transaction->paymentIntent->order ? [
                    'id' => $transaction->paymentIntent->order->public_id,
                    'order_code' => $transaction->paymentIntent->order->order_code,
                    'status' => $transaction->paymentIntent->order->status,
                ] : null,
            ] : null,
            'occurred_at' => optional($transaction->occurred_at)?->toIso8601String(),
            'created_at' => optional($transaction->created_at)?->toIso8601String(),
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

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
