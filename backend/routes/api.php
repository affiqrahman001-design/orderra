<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => 'ORDERra',
        'mode' => 'demo',
        'version' => 'v1',
    ]);
});

Route::middleware('throttle:public-api')->group(function (): void {
    if (file_exists(base_path('routes/customer.php'))) {
        require base_path('routes/customer.php');
    }
});

Route::prefix('admin/auth')
    ->middleware('throttle:admin-auth')
    ->group(function (): void {
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

Route::prefix('auth')
    ->middleware('throttle:admin-auth')
    ->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
    });

Route::prefix('auth')
    ->middleware(['auth:sanctum', 'throttle:public-api'])
    ->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

Route::prefix('admin/auth')
    ->middleware(['staff.bearer', 'throttle:admin-reference'])
    ->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
    });

Route::prefix('admin')
    ->middleware(['orderra.admin', 'throttle:admin-reference'])
    ->group(function (): void {
        if (file_exists(base_path('routes/admin.php'))) {
            require base_path('routes/admin.php');
        }
    });

Route::prefix('simulation')
    ->middleware(['orderra.admin', 'throttle:admin-reference'])
    ->group(function (): void {
        if (file_exists(base_path('routes/simulation.php'))) {
            require base_path('routes/simulation.php');
        }
    });

Route::prefix('webhooks')
    ->middleware(['orderra.admin', 'throttle:webhook-simulation'])
    ->group(function (): void {
        if (file_exists(base_path('routes/webhooks.php'))) {
            require base_path('routes/webhooks.php');
        }
    });

Route::prefix('internal')->group(function (): void {
    if (file_exists(base_path('routes/internal.php'))) {
        require base_path('routes/internal.php');
    }
});
