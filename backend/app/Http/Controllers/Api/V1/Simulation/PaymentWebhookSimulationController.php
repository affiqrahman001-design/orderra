<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Simulation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\SimulatePaymentWebhookRequest;
use App\Http\Resources\PaymentWebhookEventResource;
use App\Models\PaymentIntent;
use App\Services\Payments\SimulatePaymentWebhookService;
use Illuminate\Http\JsonResponse;

class PaymentWebhookSimulationController extends Controller
{
    public function __invoke(
        SimulatePaymentWebhookRequest $request,
        PaymentIntent $paymentIntent,
        SimulatePaymentWebhookService $simulatePaymentWebhookService
    ): JsonResponse {
        $event = $simulatePaymentWebhookService->handle($paymentIntent, $request->validated());

        return response()->json([
            'message' => 'Payment webhook simulated successfully.',
            'data' => new PaymentWebhookEventResource($event),
        ]);
    }
}
