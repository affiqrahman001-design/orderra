<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_test_environment_boots(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
    }
}
