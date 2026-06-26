<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsOrderraCustomerFlow;
use Tests\TestCase;

final class CustomerCheckoutFlowTest extends TestCase
{
    use BuildsOrderraCustomerFlow;
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureOrderraTestEnvironment();
    }

    public function test_cart_can_be_created(): void
    {
        $cart = $this->createCart();

        $cart['response']
            ->assertJsonPath('data.status', 'cart_draft')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'cart_token',
                    'status',
                    'currency',
                    'lines',
                    'totals',
                ],
            ]);
    }

    public function test_cart_line_can_be_added(): void
    {
        $cart = $this->createCartWithLine(quantity: 2);

        $cart['response']
            ->assertJsonPath('data.lines.0.item_name', $cart['item']->name)
            ->assertJsonPath('data.lines.0.quantity', 2);

        $this->assertGreaterThan(0, $cart['response']->json('data.totals.subtotal'));
    }

    public function test_pricing_quote_can_be_calculated(): void
    {
        $cart = $this->createQuotedDeliveryCart();

        $cart['response']
            ->assertJsonPath('data.fulfillment_type', 'delivery')
            ->assertJsonPath('data.customer_context.name', 'Demo Customer')
            ->assertJsonPath('data.fulfillment_context.city', 'New York');

        $this->assertGreaterThan(0, $cart['response']->json('data.totals.total'));
    }

    public function test_checkout_payment_intent_can_be_created_in_demo_mode(): void
    {
        $cart = $this->createQuotedDeliveryCart();

        $response = $this->postJson('/api/v1/payments/intents', [
            'method_code' => 'card',
            'provider_code' => 'demo_card',
            'country_code' => 'US',
            'currency' => 'USD',
            'cart_token' => $cart['cart_token'],
            'simulation_outcome' => 'success',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Payment intent created successfully.')
            ->assertJsonPath('data.method_code', 'card')
            ->assertJsonPath('data.provider_code', 'demo_card')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.currency', 'USD');

        $this->assertGreaterThan(0, $response->json('data.amount'));
    }

    public function test_payment_simulation_success_works(): void
    {
        $cart = $this->createQuotedDeliveryCart();
        $payment = $this->createSuccessfulPaymentIntentForCart($cart['cart_token']);

        $payment['simulate_response']
            ->assertJsonPath('message', 'Payment intent simulated successfully.')
            ->assertJsonPath('data.intent.status', 'succeeded')
            ->assertJsonPath('data.attempt.status', 'succeeded')
            ->assertJsonPath('data.transaction.status', 'succeeded')
            ->assertJsonPath('data.result.outcome', 'success')
            ->assertJsonPath('data.result.can_place_order', true);
    }

    public function test_order_can_be_viewed_after_checkout_payment_flow(): void
    {
        $placed = $this->placePaidDeliveryOrder();

        $placed['order_response']
            ->assertJsonPath('data.status', 'placed')
            ->assertJsonPath('data.fulfillment_type', 'delivery')
            ->assertJsonPath('data.customer_context.name', 'Demo Customer');

        $response = $this->getJson("/api/v1/orders/{$placed['order_id']}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $placed['order_id'])
            ->assertJsonPath('data.status', 'placed')
            ->assertJsonPath('data.fulfillment_type', 'delivery');

        $this->assertGreaterThan(0, count($response->json('data.items')));
    }

    public function test_refund_request_can_be_created_and_simulated(): void
    {
        $placed = $this->placePaidDeliveryOrder();

        $response = $this->postJson("/api/v1/orders/{$placed['order_id']}/refunds", [
            'category' => 'full_refund',
            'reason' => 'STEP 11 automated refund simulation.',
            'notes' => 'Demo-safe refund request created by automated test.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Refund request created successfully.')
            ->assertJsonPath('data.category', 'full_refund')
            ->assertJsonPath('data.order.id', $placed['order_id']);

        $this->assertContains($response->json('data.status'), ['requested', 'approved', 'processed']);
        $this->assertGreaterThan(0, $response->json('data.amounts.requested'));
    }
}
