<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\Payments\LivePaymentExecutionBlockedException;
use App\Models\PaymentProvider;
use App\Services\Payments\DemoPaymentGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsOrderraCustomerFlow;
use Tests\TestCase;

final class PaymentDemoGuardTest extends TestCase
{
    use BuildsOrderraCustomerFlow;
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureOrderraTestEnvironment();
    }

    public function test_live_payment_capture_is_blocked_by_demo_guard(): void
    {
        $provider = PaymentProvider::query()
            ->where('code', 'demo_card')
            ->firstOrFail();

        $provider->forceFill([
            'live_enabled' => true,
            'mode' => 'live',
        ])->save();

        $this->expectException(LivePaymentExecutionBlockedException::class);
        $this->expectExceptionMessage('Live payment execution is blocked by server-side demo guard');

        app(DemoPaymentGuard::class)->assertProviderIsDemoSafe($provider->fresh());
    }
}
