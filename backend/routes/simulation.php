<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Simulation\OpsWebhookSimulationController;
use App\Http\Controllers\Api\V1\Simulation\PaymentRefundHookSimulationController;
use App\Http\Controllers\Api\V1\Simulation\PaymentWebhookSimulationController;
use App\Http\Controllers\Api\V1\Simulation\RiderSimulationController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')
    ->middleware('throttle:payment-simulation')
    ->group(function (): void {
        Route::post('/intents/{paymentIntent:public_id}/webhooks', PaymentWebhookSimulationController::class)
            ->whereUuid('paymentIntent');

        Route::post('/intents/{paymentIntent:public_id}/refund-hooks', PaymentRefundHookSimulationController::class)
            ->whereUuid('paymentIntent');
    });

Route::prefix('riders')
    ->middleware('throttle:webhook-simulation')
    ->group(function (): void {
        Route::post('/orders/{order:public_id}/assignments', [RiderSimulationController::class, 'store'])
            ->whereUuid('order');

        Route::get('/assignments/{deliveryAssignment:public_id}', [RiderSimulationController::class, 'show'])
            ->whereUuid('deliveryAssignment');

        Route::post('/assignments/{deliveryAssignment:public_id}/advance', [RiderSimulationController::class, 'advance'])
            ->whereUuid('deliveryAssignment');
    });

Route::prefix('ops')
    ->middleware('throttle:webhook-simulation')
    ->group(function (): void {
        Route::post('/webhooks', [OpsWebhookSimulationController::class, 'store']);

        Route::get('/webhooks/{opsWebhookEvent:public_id}', [OpsWebhookSimulationController::class, 'show'])
            ->whereUuid('opsWebhookEvent');

        Route::post('/webhooks/{opsWebhookEvent:public_id}/replay', [OpsWebhookSimulationController::class, 'replay'])
            ->whereUuid('opsWebhookEvent');
    });
