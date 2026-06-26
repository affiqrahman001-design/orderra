<?php

use App\Exceptions\Payments\LivePaymentExecutionBlockedException;
use App\Http\Middleware\AuthenticateStaffBearer;
use App\Http\Middleware\EnsureAdminReferenceAccess;
use App\Http\Middleware\OrderraAdminAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.reference' => EnsureAdminReferenceAccess::class,
            'orderra.admin' => OrderraAdminAccess::class,
            'staff.bearer' => AuthenticateStaffBearer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (LivePaymentExecutionBlockedException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        });

        $exceptions->render(function (InvalidArgumentException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return null;
        });
    })
    ->create();
