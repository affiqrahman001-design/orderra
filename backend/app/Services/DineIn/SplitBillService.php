<?php

declare(strict_types=1);

namespace App\Services\DineIn;

use App\Models\QrSession;
use App\Models\SplitBillAllocation;
use App\Models\SplitBillParticipant;
use App\Models\SplitBillPlan;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SplitBillService
{
    public function createDraft(QrSession $session, array $payload): SplitBillPlan
    {
        $this->guardSessionAllowsSplit($session);
        $this->guardNoOpenAttachedCarts($session);

        $orders = $this->loadSessionOrders($session);

        if ($orders->isEmpty()) {
            throw ValidationException::withMessages([
                'session' => 'Split bill perlukan sekurang-kurangnya satu order dalam table session.',
            ]);
        }

        $sessionTotals = $this->buildSessionTotals($orders);
        $participantsPayload = $this->normalizeParticipants((array) ($payload['participants'] ?? []));

        if (count($participantsPayload) < 2) {
            throw ValidationException::withMessages([
                'participants' => 'Split bill perlukan sekurang-kurangnya 2 participant.',
            ]);
        }

        $splitType = (string) $payload['split_type'];

        $this->guardLockedPlanDoesNotExist($session);

        return DB::transaction(function () use ($session, $orders, $sessionTotals, $participantsPayload, $splitType, $payload): SplitBillPlan {
            $this->cancelExistingDraft($session);

            $plan = SplitBillPlan::create([
                'public_id' => (string) Str::uuid(),
                'qr_session_id' => $session->id,
                'split_type' => $splitType,
                'status' => 'draft',
                'currency' => (string) $sessionTotals['currency'],
                'session_totals_snapshot' => $sessionTotals,
                'rules_snapshot' => [
                    'require_full_item_allocation' => (bool) config('dine_in.split_bill.require_full_item_allocation', true),
                    'single_active_plan_per_session' => (bool) config('dine_in.split_bill.single_active_plan_per_session', true),
                ],
            ]);

            $participants = $this->createParticipants($plan, $participantsPayload);

            if ($splitType === 'equal') {
                $this->applyEqualSplit($plan, $participants, $sessionTotals);
            }

            if ($splitType === 'by_item') {
                $this->applyByItemSplit(
                    plan: $plan,
                    participants: $participants,
                    orders: $orders,
                    sessionTotals: $sessionTotals,
                    itemAllocations: (array) ($payload['item_allocations'] ?? []),
                );
            }

            $this->appendSessionEvent(
                $session,
                'split_bill_created',
                [
                    'split_bill_plan_public_id' => $plan->public_id,
                    'split_type' => $plan->split_type,
                    'participant_count' => $participants->count(),
                ],
            );

            return $this->loadPlan($plan);
        });
    }

    public function showActive(QrSession $session): SplitBillPlan
    {
        $plan = SplitBillPlan::query()
            ->where('qr_session_id', $session->id)
            ->whereIn('status', ['draft', 'finalized'])
            ->latest('id')
            ->first();

        if ($plan === null) {
            throw ValidationException::withMessages([
                'split_bill' => 'Tiada active split bill untuk session ini.',
            ]);
        }

        return $this->loadPlan($plan);
    }

    public function finalize(QrSession $session, SplitBillPlan $plan): SplitBillPlan
    {
        $this->guardPlanBelongsToSession($session, $plan);

        if (! in_array($plan->status, config('dine_in.split_bill.finalizable_statuses', ['draft']), true)) {
            throw ValidationException::withMessages([
                'split_bill' => 'Split bill ini tidak boleh difinalize lagi.',
            ]);
        }

        $plan = $this->loadPlan($plan);

        $this->guardPlanHasParticipants($plan);
        $this->guardPlanTotalsIntegrity($plan);

        $plan->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $this->appendSessionEvent(
            $session,
            'split_bill_finalized',
            [
                'split_bill_plan_public_id' => $plan->public_id,
                'split_type' => $plan->split_type,
            ],
        );

        return $this->loadPlan($plan->fresh());
    }

    private function createParticipants(SplitBillPlan $plan, array $participantsPayload): EloquentCollection
    {
        $created = [];

        foreach ($participantsPayload as $index => $participant) {
            $created[] = SplitBillParticipant::create([
                'public_id' => (string) Str::uuid(),
                'split_bill_plan_id' => $plan->id,
                'display_name' => $participant['display_name'],
                'seat_label' => $participant['seat_label'] ?? null,
                'participant_order' => $index + 1,
                'is_primary_payer' => (bool) ($participant['is_primary_payer'] ?? false),
                'status' => 'active',
                'meta' => [
                    'participant_ref' => $participant['participant_ref'],
                ],
            ]);
        }

        return new EloquentCollection($created);
    }

    private function applyEqualSplit(SplitBillPlan $plan, EloquentCollection $participants, array $sessionTotals): void
    {
        $count = $participants->count();

        $subtotal = $this->distributeEvenly((int) $sessionTotals['subtotal_amount'], $count);
        $discount = $this->distributeEvenly((int) $sessionTotals['discount_amount'], $count);
        $serviceFee = $this->distributeEvenly((int) $sessionTotals['service_fee_amount'], $count);
        $deliveryFee = $this->distributeEvenly((int) $sessionTotals['delivery_fee_amount'], $count);
        $smallOrderFee = $this->distributeEvenly((int) $sessionTotals['small_order_fee_amount'], $count);
        $tax = $this->distributeEvenly((int) $sessionTotals['tax_amount'], $count);
        $tip = $this->distributeEvenly((int) $sessionTotals['tip_amount'], $count);

        foreach ($participants->values() as $index => $participant) {
            $total = $subtotal[$index]
              - $discount[$index]
              + $serviceFee[$index]
              + $deliveryFee[$index]
              + $smallOrderFee[$index]
              + $tax[$index]
              + $tip[$index];

            $participant->update([
                'subtotal_amount' => $subtotal[$index],
                'discount_amount' => $discount[$index],
                'service_fee_amount' => $serviceFee[$index],
                'delivery_fee_amount' => $deliveryFee[$index],
                'small_order_fee_amount' => $smallOrderFee[$index],
                'tax_amount' => $tax[$index],
                'tip_amount' => $tip[$index],
                'total_amount' => $total,
            ]);

            SplitBillAllocation::create([
                'split_bill_plan_id' => $plan->id,
                'split_bill_participant_id' => $participant->id,
                'allocation_type' => 'equal_share',
                'subtotal_amount' => $subtotal[$index],
                'source_snapshot' => [
                    'split_strategy' => 'equal',
                    'participant_order' => $participant->participant_order,
                ],
            ]);
        }
    }

    private function applyByItemSplit(
        SplitBillPlan $plan,
        EloquentCollection $participants,
        EloquentCollection $orders,
        array $sessionTotals,
        array $itemAllocations,
    ): void {
        if ($itemAllocations === []) {
            throw ValidationException::withMessages([
                'item_allocations' => 'Split by item memerlukan item allocations.',
            ]);
        }

        $participantMap = [];
        foreach ($participants as $participant) {
            $participantMap[(string) ($participant->meta['participant_ref'] ?? '')] = $participant;
        }

        $orderItemMap = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $orderItemMap[$item->id] = [
                    'order' => $order,
                    'item' => $item,
                ];
            }
        }

        $participantSubtotals = [];
        foreach ($participants as $participant) {
            $participantSubtotals[$participant->id] = 0;
        }

        $assignedQuantities = [];

        foreach ($itemAllocations as $allocation) {
            $participantRef = (string) ($allocation['participant_ref'] ?? '');
            $orderItemId = (int) ($allocation['order_item_id'] ?? 0);
            $quantity = (int) ($allocation['quantity'] ?? 0);

            if (! isset($participantMap[$participantRef])) {
                throw ValidationException::withMessages([
                    'item_allocations' => "Participant ref '{$participantRef}' tidak sah.",
                ]);
            }

            if (! isset($orderItemMap[$orderItemId])) {
                throw ValidationException::withMessages([
                    'item_allocations' => "Order item '{$orderItemId}' tidak wujud dalam session ini.",
                ]);
            }

            $order = $orderItemMap[$orderItemId]['order'];
            $item = $orderItemMap[$orderItemId]['item'];

            $assignedQuantities[$orderItemId] = ($assignedQuantities[$orderItemId] ?? 0) + $quantity;

            if ($assignedQuantities[$orderItemId] > (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'item_allocations' => "Quantity untuk order item '{$orderItemId}' melebihi quantity sebenar.",
                ]);
            }

            $allocationSubtotal = (int) $item->unit_price_amount * $quantity;
            $participant = $participantMap[$participantRef];

            $participantSubtotals[$participant->id] += $allocationSubtotal;

            SplitBillAllocation::create([
                'split_bill_plan_id' => $plan->id,
                'split_bill_participant_id' => $participant->id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'allocation_type' => 'by_item',
                'quantity' => $quantity,
                'item_name' => $item->item_name,
                'item_slug' => $item->item_slug,
                'subtotal_amount' => $allocationSubtotal,
                'source_snapshot' => [
                    'order_code' => $order->order_code,
                    'order_public_id' => $order->public_id,
                    'unit_price_amount' => (int) $item->unit_price_amount,
                    'line_quantity' => (int) $item->quantity,
                ],
            ]);
        }

        if ((bool) config('dine_in.split_bill.require_full_item_allocation', true)) {
            foreach ($orderItemMap as $orderItemId => $payload) {
                $expectedQuantity = (int) $payload['item']->quantity;
                $actualQuantity = (int) ($assignedQuantities[$orderItemId] ?? 0);

                if ($expectedQuantity !== $actualQuantity) {
                    throw ValidationException::withMessages([
                        'item_allocations' => "Order item '{$orderItemId}' belum diagih penuh kepada participant.",
                    ]);
                }
            }
        }

        $subtotalSum = array_sum($participantSubtotals);

        if ($subtotalSum !== (int) $sessionTotals['subtotal_amount']) {
            throw ValidationException::withMessages([
                'item_allocations' => 'Jumlah subtotal allocation tidak sama dengan subtotal session.',
            ]);
        }

        $weights = [];
        foreach ($participants as $participant) {
            $weights[] = (int) ($participantSubtotals[$participant->id] ?? 0);
        }

        $discount = $this->distributeWeighted((int) $sessionTotals['discount_amount'], $weights);
        $serviceFee = $this->distributeWeighted((int) $sessionTotals['service_fee_amount'], $weights);
        $deliveryFee = $this->distributeWeighted((int) $sessionTotals['delivery_fee_amount'], $weights);
        $smallOrderFee = $this->distributeWeighted((int) $sessionTotals['small_order_fee_amount'], $weights);
        $tax = $this->distributeWeighted((int) $sessionTotals['tax_amount'], $weights);
        $tip = $this->distributeWeighted((int) $sessionTotals['tip_amount'], $weights);

        foreach ($participants->values() as $index => $participant) {
            $subtotal = (int) ($participantSubtotals[$participant->id] ?? 0);

            $total = $subtotal
              - $discount[$index]
              + $serviceFee[$index]
              + $deliveryFee[$index]
              + $smallOrderFee[$index]
              + $tax[$index]
              + $tip[$index];

            $participant->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount[$index],
                'service_fee_amount' => $serviceFee[$index],
                'delivery_fee_amount' => $deliveryFee[$index],
                'small_order_fee_amount' => $smallOrderFee[$index],
                'tax_amount' => $tax[$index],
                'tip_amount' => $tip[$index],
                'total_amount' => $total,
            ]);
        }
    }

    private function loadSessionOrders(QrSession $session): EloquentCollection
    {
        return $session->orders()
            ->with(['items'])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->get();
    }

    private function buildSessionTotals(EloquentCollection $orders): array
    {
        $firstOrder = $orders->first();

        return [
            'currency' => (string) ($firstOrder?->currency ?? 'USD'),
            'order_count' => $orders->count(),
            'order_codes' => $orders->pluck('order_code')->values()->all(),
            'subtotal_amount' => (int) $orders->sum('subtotal_amount'),
            'discount_amount' => (int) $orders->sum('discount_amount'),
            'service_fee_amount' => (int) $orders->sum('service_fee_amount'),
            'delivery_fee_amount' => (int) $orders->sum('delivery_fee_amount'),
            'small_order_fee_amount' => (int) $orders->sum('small_order_fee_amount'),
            'tax_amount' => (int) $orders->sum('tax_amount'),
            'tip_amount' => (int) $orders->sum('tip_amount'),
            'total_amount' => (int) $orders->sum('total_amount'),
        ];
    }

    private function normalizeParticipants(array $participants): array
    {
        $normalized = [];

        foreach ($participants as $participant) {
            $normalized[] = [
                'participant_ref' => trim((string) ($participant['participant_ref'] ?? '')),
                'display_name' => trim((string) ($participant['display_name'] ?? '')),
                'seat_label' => isset($participant['seat_label']) ? trim((string) $participant['seat_label']) : null,
                'is_primary_payer' => (bool) ($participant['is_primary_payer'] ?? false),
            ];
        }

        $hasPrimary = collect($normalized)->contains(
            fn (array $participant) => $participant['is_primary_payer'] === true
        );

        if (! $hasPrimary && isset($normalized[0])) {
            $normalized[0]['is_primary_payer'] = true;
        }

        return $normalized;
    }

    private function guardSessionAllowsSplit(QrSession $session): void
    {
        if (! (bool) config('dine_in.split_bill.enabled', true)) {
            throw ValidationException::withMessages([
                'split_bill' => 'Split bill tidak diaktifkan.',
            ]);
        }

        if (! in_array($session->status, config('dine_in.split_bill.allowed_session_statuses', []), true)) {
            throw ValidationException::withMessages([
                'split_bill' => 'Session ini belum dibenarkan untuk split bill.',
            ]);
        }
    }

    private function guardLockedPlanDoesNotExist(QrSession $session): void
    {
        $lockedPlan = SplitBillPlan::query()
            ->where('qr_session_id', $session->id)
            ->where('status', 'finalized')
            ->latest('id')
            ->first();

        if ($lockedPlan !== null) {
            throw ValidationException::withMessages([
                'split_bill' => 'Session ini sudah ada split bill finalized.',
            ]);
        }
    }

    private function cancelExistingDraft(QrSession $session): void
    {
        if (! (bool) config('dine_in.split_bill.allow_replace_draft', true)) {
            return;
        }

        SplitBillPlan::query()
            ->where('qr_session_id', $session->id)
            ->where('status', 'draft')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    private function guardPlanBelongsToSession(QrSession $session, SplitBillPlan $plan): void
    {
        if ($plan->qr_session_id !== $session->id) {
            throw ValidationException::withMessages([
                'split_bill' => 'Split bill ini bukan milik session tersebut.',
            ]);
        }
    }

    private function appendSessionEvent(QrSession $session, string $eventType, array $payload = []): void
    {
        $session->events()->create([
            'event_type' => $eventType,
            'actor_type' => 'customer',
            'actor_id' => null,
            'note' => null,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    private function distributeEvenly(int $amount, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $base = intdiv($amount, $count);
        $remainder = $amount % $count;

        $result = array_fill(0, $count, $base);

        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }

        return $result;
    }

    private function distributeWeighted(int $amount, array $weights): array
    {
        $count = count($weights);

        if ($count === 0) {
            return [];
        }

        if ($amount === 0) {
            return array_fill(0, $count, 0);
        }

        $totalWeight = array_sum($weights);

        if ($totalWeight <= 0) {
            return array_fill(0, $count, 0);
        }

        $baseAmounts = [];
        $fractions = [];
        $distributed = 0;

        foreach ($weights as $index => $weight) {
            $exact = ($amount * $weight) / $totalWeight;
            $base = (int) floor($exact);

            $baseAmounts[$index] = $base;
            $fractions[$index] = $exact - $base;
            $distributed += $base;
        }

        $remainder = $amount - $distributed;

        arsort($fractions);

        foreach (array_keys($fractions) as $index) {
            if ($remainder <= 0) {
                break;
            }

            $baseAmounts[$index]++;
            $remainder--;
        }

        ksort($baseAmounts);

        return array_values($baseAmounts);
    }

    private function guardNoOpenAttachedCarts(QrSession $session): void
    {
        if (! (bool) config('dine_in.split_bill.require_no_open_attached_carts', true)) {
            return;
        }

        $hasOpenAttachedCart = $session->cartLinks()
            ->whereHas('cart', fn ($query) => $query->doesntHave('placedOrder'))
            ->exists();

        if ($hasOpenAttachedCart) {
            throw ValidationException::withMessages([
                'split_bill' => 'Split bill belum boleh dibuat kerana masih ada cart dalam session yang belum di-place menjadi order.',
            ]);
        }
    }

    private function guardPlanHasParticipants(SplitBillPlan $plan): void
    {
        if ($plan->participants->count() < 2) {
            throw ValidationException::withMessages([
                'split_bill' => 'Split bill mesti mempunyai sekurang-kurangnya 2 participant.',
            ]);
        }
    }

    private function guardPlanTotalsIntegrity(SplitBillPlan $plan): void
    {
        if (! (bool) config('dine_in.split_bill.require_participant_totals_match_session_total', true)) {
            return;
        }

        $participantTotalSum = (int) $plan->participants->sum('total_amount');
        $sessionTotal = (int) ($plan->session_totals_snapshot['total_amount'] ?? 0);

        if ($participantTotalSum !== $sessionTotal) {
            throw ValidationException::withMessages([
                'split_bill' => 'Jumlah total participant tidak sama dengan total session.',
            ]);
        }

        if ($plan->split_type !== 'by_item') {
            return;
        }

        $allocationSubtotalSum = (int) $plan->participants
            ->flatMap(fn ($participant) => $participant->allocations)
            ->sum('subtotal_amount');

        $sessionSubtotal = (int) ($plan->session_totals_snapshot['subtotal_amount'] ?? 0);

        if ($allocationSubtotalSum !== $sessionSubtotal) {
            throw ValidationException::withMessages([
                'split_bill' => 'Jumlah subtotal allocation tidak sama dengan subtotal session.',
            ]);
        }
    }

    private function loadPlan(SplitBillPlan $plan): SplitBillPlan
    {
        return $plan->fresh([
            'qrSession.restaurantTable',
            'participants.allocations.order',
            'participants.allocations.orderItem',
        ]);
    }
}
