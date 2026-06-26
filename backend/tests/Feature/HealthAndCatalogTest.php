<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsOrderraCustomerFlow;
use Tests\TestCase;

final class HealthAndCatalogTest extends TestCase
{
    use BuildsOrderraCustomerFlow;
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureOrderraTestEnvironment();
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('app', 'ORDERra')
            ->assertJsonPath('mode', 'demo')
            ->assertJsonPath('version', 'v1');
    }

    public function test_catalog_endpoint_returns_seeded_menu(): void
    {
        $response = $this->getJson('/api/v1/catalog');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'items',
                ],
                'meta' => [
                    'demo',
                    'total_categories',
                    'total_items',
                ],
            ])
            ->assertJsonPath('meta.demo', true);

        $this->assertGreaterThan(0, count($response->json('data.categories')));
        $this->assertGreaterThan(0, count($response->json('data.items')));
        $this->assertContains('Signature Smash Burger', collect($response->json('data.items'))->pluck('name')->all());
    }
}
