<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsOrderraCustomerFlow;
use Tests\TestCase;

final class AdminReferenceGuardTest extends TestCase
{
    use BuildsOrderraCustomerFlow;
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureOrderraTestEnvironment();
    }

    public function test_admin_route_without_key_returns_forbidden(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Admin reference access denied.');
    }

    public function test_admin_route_with_valid_demo_key_returns_success(): void
    {
        $response = $this
            ->withHeader('X-ORDERra-Admin-Key', self::ADMIN_DEMO_KEY)
            ->getJson('/api/v1/admin/dashboard');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'counters' => [
                        'total_orders',
                        'revenue_demo_total',
                        'pending_orders',
                        'active_riders',
                        'refund_count',
                        'support_ticket_count',
                    ],
                ],
            ]);
    }
}
