<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpsWebhookEvent;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;

final class AdminDashboardController extends Controller
{
    public function __invoke(AdminDashboardService $dashboardService): JsonResponse
    {
        $payload = $dashboardService->build();

        return response()->json([
            'data' => [
                'counters' => $payload['counters'],
                'watchlists' => $payload['watchlists'],
                'recent_orders' => $payload['recent_orders']->map(
                    fn (Order $order) => $this->mapOrderSummary($order)
                )->values(),
                'recent_payments' => $payload['recent_payments']->map(
                    fn (PaymentIntent $paymentIntent) => $this->mapPaymentSummary($paymentIntent)
                )->values(),
                'recent_refunds' => $payload['recent_refunds']->map(
                    fn (Refund $refund) => $this->mapRefundSummary($refund)
                )->values(),
                'recent_support_tickets' => $payload['recent_support_tickets']->map(
                    fn (SupportTicket $ticket) => $this->mapSupportSummary($ticket)
                )->values(),
                'recent_webhooks' => $payload['recent_webhooks']->map(
                    fn (OpsWebhookEvent $event) => $this->mapWebhookSummary($event)
                )->values(),
            ],
        ]);
    }

    private function mapOrderSummary(Order $order): array
    {
        return [
            'id' => $order->public_id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'fulfillment_type' => $order->fulfillment_type,
            'currency' => $order->currency,
            'item_count' => (int) ($order->items_count ?? 0),
            'total_amount' => $this->toMoney((int) $order->total_amount),
            'customer_name' => data_get($order->customer_context_snapshot, 'name'),
            'allowed_transitions' => $order->allowedTransitions(),
            'placed_at' => optional($order->placed_at)?->toIso8601String(),
        ];
    }

    private function mapPaymentSummary(PaymentIntent $paymentIntent): array
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
            'order' => $paymentIntent->order ? [
                'id' => $paymentIntent->order->public_id,
                'order_code' => $paymentIntent->order->order_code,
                'status' => $paymentIntent->order->status,
            ] : null,
            'created_at' => optional($paymentIntent->created_at)?->toIso8601String(),
        ];
    }

    private function mapRefundSummary(Refund $refund): array
    {
        return [
            'id' => $refund->public_id,
            'category' => $refund->category,
            'status' => $refund->status,
            'resolution_type' => $refund->resolution_type,
            'requested_amount' => $this->toMoney((int) $refund->requested_amount),
            'order' => $refund->order ? [
                'id' => $refund->order->public_id,
                'order_code' => $refund->order->order_code,
                'status' => $refund->order->status,
            ] : null,
            'requested_at' => optional($refund->requested_at)?->toIso8601String(),
        ];
    }

    private function mapSupportSummary(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->public_id,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'subject' => $ticket->subject,
            'order' => $ticket->order ? [
                'id' => $ticket->order->public_id,
                'order_code' => $ticket->order->order_code,
                'status' => $ticket->order->status,
            ] : null,
            'opened_at' => optional($ticket->opened_at)?->toIso8601String(),
        ];
    }

    private function mapWebhookSummary(OpsWebhookEvent $event): array
    {
        return [
            'id' => $event->public_id,
            'event_name' => $event->event_name,
            'aggregate_type' => $event->aggregate_type,
            'status' => $event->status,
            'replay_count' => (int) $event->replay_count,
            'generated_at' => optional($event->generated_at)?->toIso8601String(),
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function enumValue(mixed $value): mixed
    {
        return is_object($value) && property_exists($value, 'value')
          ? $value->value
          : $value;
    }
}
