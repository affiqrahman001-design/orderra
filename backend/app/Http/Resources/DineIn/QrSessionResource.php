<?php

declare(strict_types=1);

namespace App\Http\Resources\DineIn;

use App\Support\DineInJoinUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QrSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->resource;

        $branch = $session->restaurantTable?->branch;

        return [
            'data' => [
                'id' => $session->public_id,
                'session_code' => $session->session_code,
                'status' => $session->status,
                'join_url' => DineInJoinUrl::build($session->session_code),
                'public_qr_url' => DineInJoinUrl::buildShortPublic($session->session_code),
                'expires_at' => $this->sessionExpiresHint($session),
                'can_order' => $session->isActive(),
                'party_size' => $session->party_size,
                'opened_at' => optional($session->opened_at)?->toIso8601String(),
                'last_activity_at' => optional($session->last_activity_at)?->toIso8601String(),
                'bill_requested_at' => optional($session->bill_requested_at)?->toIso8601String(),
                'closed_at' => optional($session->closed_at)?->toIso8601String(),
                'allowed_actions' => $this->allowedActions($session),
                'table' => $session->restaurantTable ? [
                    'id' => $session->restaurantTable->public_id,
                    'code' => $session->restaurantTable->code,
                    'label' => $session->restaurantTable->label,
                    'seat_capacity' => $session->restaurantTable->seat_capacity,
                    'status' => $session->restaurantTable->status,
                ] : null,
                'branch' => $branch ? [
                    'id' => $branch->public_id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                ] : null,
                'active_split_bill' => $this->activeSplitBillSummary($session),
                'linked_carts' => $session->cartLinks->map(fn ($link) => [
                    'cart_id' => $link->cart?->public_id,
                    'cart_token' => $link->cart?->cart_token,
                    'status' => $link->cart?->status,
                    'placed_order_code' => $link->cart?->placedOrder?->order_code,
                    'linked_at' => optional($link->linked_at)?->toIso8601String(),
                ])->values(),
                'linked_orders' => $session->orderLinks->map(fn ($link) => [
                    'order_id' => $link->order?->public_id,
                    'order_code' => $link->order?->order_code,
                    'status' => $link->order?->status,
                    'total_amount' => $this->toMoney((int) ($link->order?->total_amount ?? 0)),
                    'linked_from_cart_id' => $link->cart?->public_id,
                    'linked_at' => optional($link->linked_at)?->toIso8601String(),
                ])->values(),
                'events' => $session->events->take(20)->map(fn ($event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'actor_type' => $event->actor_type,
                    'actor_id' => $event->actor_id,
                    'note' => $event->note,
                    'payload' => $event->payload ?? [],
                    'order_id' => $event->order?->public_id,
                    'cart_id' => $event->cart?->public_id,
                    'created_at' => optional($event->created_at)?->toIso8601String(),
                ])->values(),
            ],
        ];
    }

    private function sessionExpiresHint($session): ?string
    {
        $ttl = config('dine_in.qr_sessions.demo_session_ttl_hours');

        if ($ttl === null || $ttl === '' || $session->opened_at === null) {
            return null;
        }

        if (! $session->isActive()) {
            return optional($session->closed_at)?->toIso8601String();
        }

        return optional($session->opened_at)?->copy()->addHours((int) $ttl)->toIso8601String();
    }

    private function allowedActions($session): array
    {
        $status = (string) $session->status;
        $hasLinkedOrders = $session->orderLinks->isNotEmpty();
        $activeSplitBill = $session->latestActiveSplitBillPlan;

        return [
            'attach_cart' => in_array($status, config('dine_in.qr_sessions.allow_attach_cart_statuses', []), true) && $activeSplitBill === null,
            'call_waiter' => in_array($status, config('dine_in.qr_sessions.allow_waiter_call_statuses', []), true),
            'request_bill' => in_array($status, config('dine_in.qr_sessions.allow_request_bill_statuses', []), true) && $hasLinkedOrders,
            'create_split_bill' => in_array($status, config('dine_in.split_bill.allowed_session_statuses', []), true) && $hasLinkedOrders && $activeSplitBill === null,
            'view_split_bill' => $activeSplitBill !== null,
        ];
    }

    private function activeSplitBillSummary($session): ?array
    {
        if (! (bool) config('dine_in.split_bill.surface_summary_on_session', true)) {
            return null;
        }

        $plan = $session->latestActiveSplitBillPlan;

        if ($plan === null) {
            return null;
        }

        return [
            'id' => $plan->public_id,
            'status' => $plan->status,
            'split_type' => $plan->split_type,
            'currency' => $plan->currency,
            'participant_count' => $plan->participants->count(),
            'participant_total_sum' => $this->toMoney((int) $plan->participants->sum('total_amount')),
            'session_total' => $this->toMoney((int) ($plan->session_totals_snapshot['total_amount'] ?? 0)),
            'finalized_at' => optional($plan->finalized_at)?->toIso8601String(),
            'created_at' => optional($plan->created_at)?->toIso8601String(),
            'is_locked' => $plan->isLocked(),
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
