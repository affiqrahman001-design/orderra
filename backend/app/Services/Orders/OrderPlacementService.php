<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\PaymentIntentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\DineIn\DineInSessionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OrderPlacementService
{
    public function __construct(
        private readonly OrderCodeGenerator $orderCodeGenerator,
        private readonly DineInSessionService $dineInSessionService,
    ) {}

    public function placeFromCartToken(string $cartToken, string $paymentIntentPublicId): Order
    {
        return DB::transaction(function () use ($cartToken, $paymentIntentPublicId): Order {
            /** @var Cart|null $cart */
            $cart = Cart::with(['lines', 'placedOrder'])
                ->where('cart_token', $cartToken)
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw ValidationException::withMessages([
                    'cart_token' => 'Cart tidak dijumpai.',
                ]);
            }

            if ($cart->placedOrder !== null) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart ini sudah ditukar menjadi order.',
                ]);
            }

            if ($cart->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart kosong dan tidak boleh di-place.',
                ]);
            }

            $pricingSnapshot = (array) ($cart->pricing_snapshot ?? []);

            if ($pricingSnapshot === [] || ! array_key_exists('total', $pricingSnapshot)) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart belum mempunyai pricing snapshot yang sah.',
                ]);
            }

            $this->validatePlacementContext($cart);

            $paymentIntent = $this->resolvePaymentIntent($paymentIntentPublicId);
            $this->validatePaymentIntentForCart($paymentIntent, $cart, $pricingSnapshot);

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'order_code' => $this->orderCodeGenerator->generate(),
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'status' => (string) config('orders.placement.initial_status', 'placed'),
                'currency' => $cart->currency,
                'fulfillment_type' => $cart->fulfillment_type,
                'source' => $cart->source ?? 'web',
                'customer_context_snapshot' => $cart->customer_context ?? [],
                'fulfillment_context_snapshot' => $cart->fulfillment_context ?? [],
                'pricing_snapshot' => $pricingSnapshot,
                'subtotal_amount' => (int) ($pricingSnapshot['subtotal'] ?? 0),
                'discount_amount' => (int) ($pricingSnapshot['discount'] ?? 0),
                'service_fee_amount' => (int) ($pricingSnapshot['service_fee'] ?? 0),
                'delivery_fee_amount' => (int) ($pricingSnapshot['delivery_fee'] ?? 0),
                'small_order_fee_amount' => (int) ($pricingSnapshot['small_order_fee'] ?? 0),
                'tax_amount' => (int) ($pricingSnapshot['tax'] ?? 0),
                'tip_amount' => (int) ($pricingSnapshot['tip'] ?? 0),
                'total_amount' => (int) ($pricingSnapshot['total'] ?? 0),
                'meta' => [
                    'pricing_meta' => $pricingSnapshot['meta'] ?? [],
                    'placed_from_cart_public_id' => $cart->public_id,
                    'payment_intent_id' => $paymentIntent->public_id,
                    'payment_intent_code' => $paymentIntent->intent_code,
                    'payment_method_code' => $this->enumValue($paymentIntent->method_code),
                    'payment_provider_code' => $this->enumValue($paymentIntent->provider_code),
                    'payment_status' => $this->enumValue($paymentIntent->status),
                    'demo_safe_payment' => true,
                ],
                'placed_at' => now(),
            ]);

            foreach ($cart->lines as $line) {
                $order->items()->create([
                    'cart_item_id' => $line->id,
                    'menu_item_id' => $line->menu_item_id,
                    'variant_id' => $line->variant_id,
                    'item_name' => $line->item_name,
                    'item_slug' => $line->item_slug,
                    'item_snapshot' => $line->item_snapshot ?? [],
                    'modifier_snapshot' => $line->modifier_snapshot ?? [],
                    'quantity' => $line->quantity,
                    'unit_base_amount' => $line->unit_base_amount,
                    'unit_modifier_amount' => $line->unit_modifier_amount,
                    'unit_price_amount' => $line->unit_price_amount,
                    'line_subtotal_amount' => $line->line_subtotal_amount,
                    'note' => $line->note,
                    'sort_order' => $line->sort_order,
                ]);
            }

            $order->fulfillment()->create($this->buildFulfillmentPayload($cart));

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => $order->status,
                'changed_by_type' => 'customer',
                'changed_by_id' => $order->user_id,
                'reason' => 'order_placed_after_demo_payment',
                'meta' => [
                    'source' => $order->source,
                    'payment_intent_id' => $paymentIntent->public_id,
                    'payment_status' => $this->enumValue($paymentIntent->status),
                ],
            ]);

            $paymentIntent->update([
                'order_id' => $order->id,
                'meta' => array_merge((array) ($paymentIntent->meta ?? []), [
                    'order_public_id' => $order->public_id,
                    'order_code' => $order->order_code,
                    'attached_to_order_at' => now()->toIso8601String(),
                ]),
            ]);

            if ($order->fulfillment_type === 'dine_in') {
                $this->dineInSessionService->linkOrderFromCart($cart, $order);
            }

            return $order->fresh([
                'items',
                'fulfillment',
                'statusHistory',
                'paymentIntents',
                'refunds.events',
                'supportTickets.events',
                'deliveryAssignment.rider',
                'deliveryAssignment.trackingEvents',
            ]);
        });
    }

    private function resolvePaymentIntent(string $paymentIntentPublicId): PaymentIntent
    {
        $paymentIntent = PaymentIntent::query()
            ->where('public_id', $paymentIntentPublicId)
            ->lockForUpdate()
            ->first();

        if (! $paymentIntent) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent tidak dijumpai.',
            ]);
        }

        return $paymentIntent;
    }

    private function validatePaymentIntentForCart(PaymentIntent $paymentIntent, Cart $cart, array $pricingSnapshot): void
    {
        $status = $this->enumValue($paymentIntent->status);
        $allowedStatuses = [
            PaymentIntentStatus::SUCCEEDED->value,
            PaymentIntentStatus::AUTHORIZED->value,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment belum berjaya. Hanya payment succeeded atau authorized boleh place order.',
            ]);
        }

        if ((int) $paymentIntent->cart_id !== (int) $cart->id) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent tidak sepadan dengan cart ini.',
            ]);
        }

        if ($paymentIntent->order_id !== null) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment intent ini sudah digunakan untuk order lain.',
            ]);
        }

        if (strtoupper((string) $paymentIntent->currency) !== strtoupper((string) $cart->currency)) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Currency payment intent tidak sama dengan currency cart.',
            ]);
        }

        $cartTotal = (int) ($pricingSnapshot['total'] ?? 0);

        if ($cartTotal <= 0) {
            throw ValidationException::withMessages([
                'cart' => 'Cart total tidak sah.',
            ]);
        }

        if ((int) $paymentIntent->amount !== $cartTotal) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment amount tidak sama dengan cart total semasa.',
            ]);
        }
    }

    private function validatePlacementContext(Cart $cart): void
    {
        $type = (string) $cart->fulfillment_type;
        $customer = (array) ($cart->customer_context ?? []);
        $context = (array) ($cart->fulfillment_context ?? []);

        if (! in_array($type, config('fulfillment.types', []), true)) {
            throw ValidationException::withMessages([
                'fulfillment_type' => 'Fulfillment type tidak sah.',
            ]);
        }

        if ($type === 'delivery') {
            $this->requireFields($customer, config('fulfillment.delivery.required_customer_fields', []), 'customer_context');
            $this->requireFields($context, config('fulfillment.delivery.required_address_fields', []), 'fulfillment_context');

            return;
        }

        if ($type === 'pickup') {
            $this->requireFields($customer, config('fulfillment.pickup.required_customer_fields', []), 'customer_context');

            return;
        }

        if ($type === 'dine_in') {
            $this->requireFields($context, config('fulfillment.dine_in.required_context_fields', []), 'fulfillment_context');
        }
    }

    private function requireFields(array $source, array $fields, string $root): void
    {
        $errors = [];

        foreach ($fields as $field) {
            $value = $source[$field] ?? null;

            if ($value === null || $value === '') {
                $errors["{$root}.{$field}"] = "{$field} wajib diisi.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function buildFulfillmentPayload(Cart $cart): array
    {
        $type = (string) $cart->fulfillment_type;
        $customer = (array) ($cart->customer_context ?? []);
        $context = (array) ($cart->fulfillment_context ?? []);

        $scheduledFor = null;

        if (! empty($context['scheduled_for'])) {
            $scheduledFor = Carbon::parse((string) $context['scheduled_for']);
        }

        return [
            'branch_id' => $context['branch_id'] ?? null,
            'fulfillment_type' => $type,
            'contact_name' => $customer['name'] ?? null,
            'contact_phone' => $customer['phone'] ?? null,
            'scheduled_for' => $scheduledFor,
            'eta_minutes' => isset($context['eta_minutes']) ? (int) $context['eta_minutes'] : null,
            'pickup_code' => $type === 'pickup' ? $this->generatePickupCode() : null,
            'table_label' => $type === 'dine_in' ? ($context['table_label'] ?? null) : null,
            'party_size' => $type === 'dine_in'
              ? (int) ($context['party_size'] ?? config('fulfillment.dine_in.default_party_size', 1))
              : null,
            'address_snapshot' => $type === 'delivery'
              ? [
                  'address_line1' => $context['address_line1'] ?? null,
                  'address_line2' => $context['address_line2'] ?? null,
                  'city' => $context['city'] ?? null,
                  'state' => $context['state'] ?? null,
                  'postal_code' => $context['postal_code'] ?? null,
                  'country_code' => $context['country_code'] ?? null,
                  'delivery_notes' => $context['delivery_notes'] ?? null,
                  'zone_code' => $context['zone_code'] ?? null,
                  'distance_meters' => $context['distance_meters'] ?? null,
              ]
              : null,
            'context_snapshot' => $context,
        ];
    }

    private function generatePickupCode(): string
    {
        $length = (int) config('fulfillment.pickup.pickup_code_length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function enumValue(mixed $value): string
    {
        return is_object($value) && property_exists($value, 'value')
          ? (string) $value->value
          : (string) $value;
    }
}
