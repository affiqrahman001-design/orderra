<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Simulation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRefundHookRequest;
use App\Http\Resources\PaymentRefundHookResource;
use App\Models\PaymentIntent;
use App\Services\Payments\CreatePaymentRefundHookService;
use Illuminate\Http\JsonResponse;

class PaymentRefundHookSimulationController extends Controller
{
    public function __invoke(
        StorePaymentRefundHookRequest $request,
        PaymentIntent $paymentIntent,
        CreatePaymentRefundHookService $createPaymentRefundHookService
    ): JsonResponse {
        $hook = $createPaymentRefundHookService->handle($paymentIntent, $request->validated());

        return response()->json([
            'message' => 'Payment refund hook recorded successfully.',
            'data' => new PaymentRefundHookResource($hook),
        ]);
    }
}
