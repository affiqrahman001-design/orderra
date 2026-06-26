<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Simulation\OpsWebhookSimulationController;
use Illuminate\Support\Facades\Route;

Route::post('/simulate', [OpsWebhookSimulationController::class, 'store'])
    ->middleware('throttle:webhook-simulation');
