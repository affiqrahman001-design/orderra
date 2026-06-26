<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\DeliveryAssignment;
use App\Models\OpsWebhookEvent;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\QrSession;
use App\Models\Refund;
use App\Models\Rider;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

final class AdminDashboardService
{
    public function build(): array
    {
        $recentLimit = (int) config('admin.dashboard.recent_limit', 5);

        $activeOrderStatuses = (array) config('admin.dashboard.active_order_statuses', []);
        $refundAttentionStatuses = (array) config('admin.dashboard.refund_attention_statuses', []);
        $supportAttentionStatuses = (array) config('admin.dashboard.support_attention_statuses', []);
        $activeQrSessionStatuses = (array) config('admin.dashboard.active_qr_session_statuses', []);
        $activeDeliveryStatuses = (array) config('admin.dashboard.active_delivery_assignment_statuses', []);
        $pendingPaymentStatuses = (array) config('admin.dashboard.pending_payment_intent_statuses', []);

        return [
            'counters' => [
                'total_orders' => Order::count(),
                'revenue_demo_total' => round((int) Order::query()
                    ->whereNotIn('status', ['cart_draft', 'pending_payment', 'cancelled', 'refunded'])
                    ->sum('total_amount') / 100, 2),
                'pending_orders' => Order::whereIn('status', [
                    'pending_payment',
                    'payment_authorized',
                    'placed',
                    'confirmed',
                    'preparing',
                    'ready',
                    'awaiting_rider',
                    'rider_assigned',
                    'picked_up',
                    'near_customer',
                    'ready_for_pickup',
                    'served',
                    'bill_requested',
                ])->count(),
                'active_riders' => Rider::where('status', 'active')->count(),
                'refund_count' => Refund::count(),
                'support_ticket_count' => SupportTicket::count(),

                // Backward-compatible counters used by earlier admin reference UI/tests.
                'orders_total' => Order::count(),
                'orders_active' => Order::whereIn('status', $activeOrderStatuses)->count(),
                'payments_pending' => PaymentIntent::whereIn('status', $pendingPaymentStatuses)->count(),
                'refunds_attention' => Refund::whereIn('status', $refundAttentionStatuses)->count(),
                'support_attention' => SupportTicket::whereIn('status', $supportAttentionStatuses)->count(),
                'qr_sessions_active' => QrSession::whereIn('status', $activeQrSessionStatuses)->count(),
                'delivery_assignments_active' => DeliveryAssignment::whereIn('status', $activeDeliveryStatuses)->count(),
                'ops_webhooks_failed' => OpsWebhookEvent::where('status', 'failed')->count(),
            ],

            'watchlists' => [
                'orders_by_status' => $this->buildBreakdown(
                    table: 'orders',
                    column: 'status',
                    knownValues: array_values(array_unique(array_merge(
                        array_keys((array) config('orders.transitions.common', [])),
                        array_keys((array) config('orders.transitions.delivery', [])),
                        array_keys((array) config('orders.transitions.pickup', [])),
                        array_keys((array) config('orders.transitions.dine_in', [])),
                        (array) config('orders.terminal_statuses', [])
                    )))
                ),
                'refunds_by_status' => $this->buildBreakdown(
                    table: 'refunds',
                    column: 'status',
                    knownValues: (array) config('refunds.statuses', [])
                ),
                'support_by_status' => $this->buildBreakdown(
                    table: 'support_tickets',
                    column: 'status',
                    knownValues: (array) config('support.statuses', [])
                ),
                'ops_webhooks_by_status' => $this->buildBreakdown(
                    table: 'ops_webhook_events',
                    column: 'status',
                    knownValues: (array) config('ops.webhooks.statuses', [])
                ),
            ],

            'recent_orders' => Order::query()
                ->with(['fulfillment', 'deliveryAssignment.rider'])
                ->withCount('items')
                ->latest('id')
                ->limit($recentLimit)
                ->get(),

            'recent_payments' => PaymentIntent::query()
                ->with(['order'])
                ->withCount(['attempts', 'transactions', 'refunds'])
                ->latest('id')
                ->limit($recentLimit)
                ->get(),

            'recent_refunds' => Refund::query()
                ->with(['order', 'paymentIntent'])
                ->latest('id')
                ->limit($recentLimit)
                ->get(),

            'recent_support_tickets' => SupportTicket::query()
                ->with(['order', 'refund', 'paymentIntent'])
                ->latest('id')
                ->limit($recentLimit)
                ->get(),

            'recent_webhooks' => OpsWebhookEvent::query()
                ->with(['order', 'refund', 'paymentIntent', 'deliveryAssignment'])
                ->latest('id')
                ->limit($recentLimit)
                ->get(),
        ];
    }

    private function buildBreakdown(string $table, string $column, array $knownValues): array
    {
        $rows = DB::table($table)
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->all();

        $breakdown = [];

        foreach ($knownValues as $value) {
            $breakdown[(string) $value] = (int) ($rows[$value] ?? 0);
        }

        foreach ($rows as $value => $total) {
            if (! array_key_exists((string) $value, $breakdown)) {
                $breakdown[(string) $value] = (int) $total;
            }
        }

        return $breakdown;
    }
}
