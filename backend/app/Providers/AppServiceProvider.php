<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('public-api', function (Request $request): Limit {
            $key = $request->ip().'|public|'.$request->path();

            return Limit::perMinute((int) env('PUBLIC_API_RATE_LIMIT', 120))->by($key);
        });

        RateLimiter::for('admin-reference', function (Request $request): Limit {
            $maxAttempts = max(10, (int) config('admin_reference.rate_limit.per_minute', 60));
            $key = $request->ip().'|admin|'.$request->path();

            return Limit::perMinute($maxAttempts)->by($key);
        });

        RateLimiter::for('admin-auth', function (Request $request): Limit {
            $key = $request->ip().'|admin-auth|'.$request->path();

            return Limit::perMinute(max(8, (int) env('ADMIN_LOGIN_RATE_LIMIT', 12)))->by($key);
        });

        RateLimiter::for('payment-simulation', function (Request $request): Limit {
            $key = $request->ip().'|payment-simulation|'.$request->path();

            return Limit::perMinute((int) env('PAYMENT_SIMULATION_RATE_LIMIT', 20))->by($key);
        });

        RateLimiter::for('webhook-simulation', function (Request $request): Limit {
            $key = $request->ip().'|webhook-simulation|'.$request->path();

            return Limit::perMinute((int) env('WEBHOOK_SIMULATION_RATE_LIMIT', 15))->by($key);
        });
    }
}
