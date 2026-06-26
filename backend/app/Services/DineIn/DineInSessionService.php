<?php

declare(strict_types=1);

namespace App\Services\DineIn;

use App\Models\Cart;
use App\Models\Order;
use App\Models\QrSession;
use App\Models\QrSessionCart;
use App\Models\QrSessionOrder;
use App\Models\RestaurantTable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DineInSessionService
{
    public function open(array $payload): QrSession
    {
        $table = $this->resolveTable($payload);
        $partySize = (int) ($payload['party_size'] ?? config('dine_in.qr_sessions.default_party_size', 1));
        $source = (string) ($payload['source'] ?? 'qr');

        return DB::transaction(function () use ($payload, $table, $partySize, $source): QrSession {
            $session = $this->findReusableActiveSession($table);

            if ($session === null) {
                $session = QrSession::create([
                    'public_id' => (string) Str::uuid(),
                    'restaurant_table_id' => $table->id,
                    'session_code' => $this->generateSessionCode(),
                    'status' => (string) config('dine_in.qr_sessions.default_status', 'open'),
                    'party_size' => $partySize,
                    'opened_via' => $source,
                    'meta' => [
                        'strategy' => config('dine_in.add_more_items.strategy'),
                    ],
                    'opened_at' => now(),
                    'last_activity_at' => now(),
                ]);

                $this->appendEvent(
                    $session,
                    eventType: 'session_opened',
                    note: sprintf('QR session dibuka untuk meja %s.', $table->label),
                    payload: [
                        'table_code' => $table->code,
                        'party_size' => $partySize,
                        'opened_via' => $source,
                    ],
                );
            }

            if (! empty($payload['cart_token'])) {
                $session = $this->attachCart($session, (string) $payload['cart_token']);
            }

            return $this->loadSession($session);
        });
    }

    public function show(QrSession $session): QrSession
    {
        return $this->loadSession($session);
    }

    /**
     * Closes active dine-in QR sessions tied to this table and opens a fresh demo session.
     *
     * @param  array{party_size?:int|null,source?:string}  $payload
     */
    public function rotateQrSessionForTable(RestaurantTable $table, array $payload = [], ?string $rotationNote = null): QrSession
    {
        $activeStatuses = (array) config('dine_in.qr_sessions.active_statuses', []);

        return DB::transaction(function () use ($table, $activeStatuses, $payload, $rotationNote): QrSession {
            $sessions = QrSession::query()
                ->where('restaurant_table_id', $table->id)
                ->whereIn('status', $activeStatuses)
                ->orderBy('id')
                ->get();

            $note = $rotationNote ?: 'QR session rotated from ORDERra admin.';

            foreach ($sessions as $session) {
                if (! $session->isActive()) {
                    continue;
                }

                $this->expire($session, $note);
            }

            return $this->open([
                'table_code' => $table->code,
                'source' => (string) ($payload['source'] ?? 'admin_rotate'),
                'party_size' => isset($payload['party_size'])
                  ? (int) $payload['party_size']
                  : (int) config('dine_in.qr_sessions.default_party_size', 1),
            ]);
        });
    }

    public function attachCart(QrSession $session, string $cartToken): QrSession
    {
        $this->guardSessionAllows($session, 'allow_attach_cart_statuses', 'Session ini tidak menerima add-more-items lagi.');
        $this->guardNoActiveSplitBill($session);

        /** @var Cart|null $cart */
        $cart = Cart::with(['lines', 'placedOrder'])->where('cart_token', $cartToken)->first();

        if ($cart === null) {
            throw ValidationException::withMessages([
                'cart_token' => 'Cart token tidak dijumpai.',
            ]);
        }

        if ($cart->placedOrder !== null) {
            throw ValidationException::withMessages([
                'cart_token' => 'Cart ini sudah menjadi order dan tidak boleh di-attach semula.',
            ]);
        }

        return DB::transaction(function () use ($session, $cart): QrSession {
            $context = array_merge(
                (array) ($cart->fulfillment_context ?? []),
                [
                    'table_label' => $session->restaurantTable->label,
                    'table_code' => $session->restaurantTable->code,
                    'restaurant_table_public_id' => $session->restaurantTable->public_id,
                    'qr_session_public_id' => $session->public_id,
                    'qr_session_code' => $session->session_code,
                    'party_size' => $session->party_size,
                ],
            );

            $cart->update([
                'fulfillment_type' => 'dine_in',
                'fulfillment_context' => $context,
            ]);

            QrSessionCart::firstOrCreate(
                [
                    'qr_session_id' => $session->id,
                    'cart_id' => $cart->id,
                ],
                [
                    'linked_at' => now(),
                ],
            );

            $this->touchSession($session);

            $this->appendEvent(
                $session,
                eventType: 'cart_attached',
                cart: $cart,
                note: 'Cart dine-in dihubungkan kepada table session.',
                payload: [
                    'cart_public_id' => $cart->public_id,
                    'cart_token' => $cart->cart_token,
                    'line_count' => $cart->lines->count(),
                ],
            );

            return $this->loadSession($session->fresh());
        });
    }

    public function callWaiter(QrSession $session, ?string $note = null): QrSession
    {
        $this->guardSessionAllows($session, 'allow_waiter_call_statuses', 'Waiter call tidak dibenarkan untuk status session ini.');
        $this->guardWaiterCooldown($session);

        $this->appendEvent(
            $session,
            eventType: 'waiter_called',
            note: $note ?: 'Customer memanggil waiter.',
        );

        $this->touchSession($session);

        return $this->loadSession($session->fresh());
    }

    public function requestBill(QrSession $session, ?string $note = null): QrSession
    {
        $this->guardSessionAllows($session, 'allow_request_bill_statuses', 'Bill belum boleh diminta untuk session ini.');

        if (
            (bool) config('dine_in.qr_sessions.require_linked_orders_for_bill_request', true)
            && ! $session->orderLinks()->exists()
        ) {
            throw ValidationException::withMessages([
                'session' => 'Bill belum boleh diminta kerana session ini belum ada order.',
            ]);
        }

        if ($session->status === 'bill_requested' && $session->bill_requested_at !== null) {
            return $this->loadSession($session);
        }

        $session->update([
            'status' => 'bill_requested',
            'bill_requested_at' => $session->bill_requested_at ?? now(),
            'last_activity_at' => now(),
        ]);

        $this->appendEvent(
            $session,
            eventType: 'bill_requested',
            note: $note ?: 'Customer meminta bil.',
        );

        return $this->loadSession($session->fresh());
    }

    public function expire(QrSession $session, ?string $note = null): QrSession
    {
        if ($session->status === 'expired') {
            return $this->loadSession($session);
        }

        if (! $session->isActive()) {
            throw ValidationException::withMessages([
                'session' => 'Session ini bukan active session dan tidak boleh di-expire.',
            ]);
        }

        $session->update([
            'status' => 'expired',
            'closed_at' => $session->closed_at ?? now(),
            'last_activity_at' => now(),
        ]);

        $session->events()->create([
            'event_type' => 'session_expired',
            'actor_type' => 'system',
            'actor_id' => null,
            'note' => $note ?: 'QR session expired by demo simulation.',
            'payload' => [
                'demo_safe' => true,
            ],
            'created_at' => now(),
        ]);

        return $this->loadSession($session->fresh());
    }

    public function linkOrderFromCart(Cart $cart, Order $order): void
    {
        $session = QrSession::query()
            ->whereHas('cartLinks', fn ($query) => $query->where('cart_id', $cart->id))
            ->with(['restaurantTable'])
            ->first();

        if ($session === null) {
            $publicId = Arr::get($cart->fulfillment_context ?? [], 'qr_session_public_id');

            if ($publicId !== null) {
                $session = QrSession::with(['restaurantTable'])->where('public_id', $publicId)->first();
            }
        }

        if ($session === null) {
            return;
        }

        DB::transaction(function () use ($session, $cart, $order): void {
            QrSessionOrder::firstOrCreate(
                [
                    'qr_session_id' => $session->id,
                    'order_id' => $order->id,
                ],
                [
                    'linked_from_cart_id' => $cart->id,
                    'linked_at' => now(),
                ],
            );

            $this->appendEvent(
                $session,
                eventType: 'order_linked',
                order: $order,
                cart: $cart,
                note: 'Order baharu dimasukkan ke dalam table session sedia ada.',
                payload: [
                    'order_code' => $order->order_code,
                    'status' => $order->status,
                ],
            );

            $this->touchSession($session);
        });
    }

    private function resolveTable(array $payload): RestaurantTable
    {
        $tableCode = isset($payload['table_code']) ? trim((string) $payload['table_code']) : null;
        $tableLabel = isset($payload['table_label']) ? trim((string) $payload['table_label']) : null;

        $query = RestaurantTable::query()->where('status', 'active');

        if ($tableCode !== null && $tableCode !== '') {
            $table = (clone $query)->where('code', $tableCode)->first();

            if ($table !== null) {
                return $table;
            }
        }

        if ($tableLabel !== null && $tableLabel !== '') {
            $table = (clone $query)->where('label', $tableLabel)->first();

            if ($table !== null) {
                return $table;
            }
        }

        throw ValidationException::withMessages([
            'table_code' => 'Meja dine-in tidak dijumpai atau tidak aktif.',
        ]);
    }

    private function findReusableActiveSession(RestaurantTable $table): ?QrSession
    {
        if (! config('dine_in.qr_sessions.reuse_open_session', true)) {
            return null;
        }

        return QrSession::query()
            ->where('restaurant_table_id', $table->id)
            ->whereIn('status', config('dine_in.qr_sessions.active_statuses', []))
            ->latest('id')
            ->first();
    }

    private function generateSessionCode(): string
    {
        $prefix = (string) config('dine_in.qr_sessions.code_prefix', 'QR');
        $length = (int) config('dine_in.qr_sessions.code_length', 6);

        do {
            $random = strtoupper(Str::random($length));
            $code = sprintf('%s-%s', $prefix, $random);
        } while (QrSession::where('session_code', $code)->exists());

        return $code;
    }

    private function touchSession(QrSession $session): void
    {
        $session->forceFill([
            'last_activity_at' => now(),
        ])->save();
    }

    private function appendEvent(
        QrSession $session,
        string $eventType,
        ?Cart $cart = null,
        ?Order $order = null,
        ?string $note = null,
        array $payload = [],
    ): void {
        $session->events()->create([
            'cart_id' => $cart?->id,
            'order_id' => $order?->id,
            'event_type' => $eventType,
            'actor_type' => 'customer',
            'actor_id' => null,
            'note' => $note,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    private function guardSessionAllows(QrSession $session, string $configKey, string $message): void
    {
        if (! in_array($session->status, config("dine_in.qr_sessions.{$configKey}", []), true)) {
            throw ValidationException::withMessages([
                'session' => $message,
            ]);
        }
    }

    private function guardWaiterCooldown(QrSession $session): void
    {
        $cooldown = (int) config('dine_in.waiter_calls.cooldown_seconds', 60);
        $latest = $session->events()
            ->where('event_type', 'waiter_called')
            ->latest('id')
            ->first();

        if ($latest === null || $latest->created_at === null) {
            return;
        }

        if ($latest->created_at->diffInSeconds(now()) < $cooldown) {
            throw ValidationException::withMessages([
                'session' => 'Waiter call baru sahaja dihantar. Cuba lagi sebentar.',
            ]);
        }
    }

    private function guardNoActiveSplitBill(QrSession $session): void
    {
        $session->loadMissing('latestActiveSplitBillPlan');

        if ($session->latestActiveSplitBillPlan !== null) {
            throw ValidationException::withMessages([
                'session' => 'Session ini sudah ada split bill aktif. Tambah item baharu tidak dibenarkan lagi.',
            ]);
        }
    }

    private function loadSession(QrSession $session): QrSession
    {
        return $session->fresh([
            'restaurantTable.branch',
            'events.order',
            'events.cart',
            'cartLinks.cart.placedOrder',
            'orderLinks.order',
            'orderLinks.cart',
            'latestActiveSplitBillPlan.participants',
        ]);
    }
}
