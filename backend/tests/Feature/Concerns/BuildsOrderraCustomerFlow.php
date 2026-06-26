<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Models\MenuItem;
use Illuminate\Testing\TestResponse;

trait BuildsOrderraCustomerFlow
{
    protected const ADMIN_DEMO_KEY = 'orderra-local-admin-demo-key';

    protected function configureOrderraTestEnvironment(): void
    {
        config([
            'admin_reference.guard.enabled' => true,
            'admin_reference.guard.header_name' => 'X-ORDERra-Admin-Key',
            'admin_reference.guard.token' => self::ADMIN_DEMO_KEY,
            'admin_reference.guard.readonly_mode' => false,
            'payments.demo_mode' => true,
            'payments.block_live_execution' => true,
            'payments.allow_webhook_simulation' => true,
        ]);
    }

    /**
     * @return array{token:string,response:TestResponse}
     */
    protected function createCart(): array
    {
        $response = $this->postJson('/api/v1/cart');
        $response->assertCreated();

        $token = (string) ($response->headers->get((string) config('cart.token_header')) ?: $response->json('data.cart_token'));

        $this->assertNotSame('', $token, 'Cart token must be returned by header or response body.');

        return [
            'token' => $token,
            'response' => $response,
        ];
    }

    /**
     * @return array{cart_token:string,item:MenuItem,response:TestResponse}
     */
    protected function createCartWithLine(int $quantity = 1): array
    {
        $cart = $this->createCart();
        $item = $this->demoMenuItem();

        $response = $this
            ->withHeader((string) config('cart.token_header'), $cart['token'])
            ->postJson('/api/v1/cart/lines', [
                'menu_item_id' => $item->public_id,
                'quantity' => $quantity,
                'note' => 'Automated STEP 11 test line.',
            ]);

        $response->assertOk();

        return [
            'cart_token' => $cart['token'],
            'item' => $item,
            'response' => $response,
        ];
    }

    /**
     * @return array{cart_token:string,item:MenuItem,response:TestResponse}
     */
    protected function createQuotedDeliveryCart(): array
    {
        $cart = $this->createCartWithLine();

        $response = $this->postJson('/api/v1/pricing/quote', $this->deliveryQuotePayload($cart['cart_token']));
        $response->assertOk();

        return [
            'cart_token' => $cart['cart_token'],
            'item' => $cart['item'],
            'response' => $response,
        ];
    }

    /**
     * @return array{cart_token:string,intent_id:string,create_response:TestResponse,simulate_response:TestResponse}
     */
    protected function createSuccessfulPaymentIntentForCart(string $cartToken): array
    {
        $createResponse = $this->postJson('/api/v1/payments/intents', [
            'method_code' => 'card',
            'provider_code' => 'demo_card',
            'country_code' => 'US',
            'currency' => 'USD',
            'cart_token' => $cartToken,
            'simulation_outcome' => 'success',
            'meta' => [
                'source' => 'step_11_test',
            ],
        ]);

        $createResponse->assertCreated();

        $intentId = (string) $createResponse->json('data.public_id');
        $this->assertNotSame('', $intentId, 'Payment intent public id must be returned.');

        $simulateResponse = $this->postJson("/api/v1/payments/intents/{$intentId}/simulate", [
            'simulation_outcome' => 'success',
        ]);

        $simulateResponse->assertOk();

        return [
            'cart_token' => $cartToken,
            'intent_id' => $intentId,
            'create_response' => $createResponse,
            'simulate_response' => $simulateResponse,
        ];
    }

    /**
     * @return array{cart_token:string,intent_id:string,order_id:string,order_response:TestResponse}
     */
    protected function placePaidDeliveryOrder(): array
    {
        $cart = $this->createQuotedDeliveryCart();
        $payment = $this->createSuccessfulPaymentIntentForCart($cart['cart_token']);

        $orderResponse = $this->postJson('/api/v1/checkout', [
            'cart_token' => $cart['cart_token'],
            'payment_intent_id' => $payment['intent_id'],
        ]);

        $orderResponse->assertCreated();

        $orderId = (string) $orderResponse->json('data.id');
        $this->assertNotSame('', $orderId, 'Order public id must be returned.');

        return [
            'cart_token' => $cart['cart_token'],
            'intent_id' => $payment['intent_id'],
            'order_id' => $orderId,
            'order_response' => $orderResponse,
        ];
    }

    protected function demoMenuItem(): MenuItem
    {
        return MenuItem::query()
            ->where('code', 'SIGNATURE_SMASH_BURGER')
            ->orWhere('is_active', true)
            ->orderByRaw("case when code = 'SIGNATURE_SMASH_BURGER' then 0 else 1 end")
            ->firstOrFail();
    }

    /**
     * @return array<string,mixed>
     */
    protected function deliveryQuotePayload(string $cartToken): array
    {
        return [
            'cart_token' => $cartToken,
            'fulfillment_type' => 'delivery',
            'customer_context' => [
                'name' => 'Demo Customer',
                'phone' => '+1 212 555 0100',
                'email' => 'customer@orderra.test',
            ],
            'fulfillment_context' => [
                'address_line1' => '128 Hudson Street',
                'address_line2' => 'Apt Demo',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10013',
                'country_code' => 'US',
                'delivery_notes' => 'Automated STEP 11 test delivery.',
            ],
            'tip_type' => 'none',
            'tip_value' => 0,
        ];
    }
}
