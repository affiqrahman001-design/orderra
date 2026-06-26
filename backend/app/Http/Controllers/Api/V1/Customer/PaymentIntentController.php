<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\SimulatePaymentIntentRequest;
use App\Http\Requests\Payments\StorePaymentIntentRequest;
use App\Http\Resources\PaymentAttemptResource;
use App\Http\Resources\PaymentIntentResource;
use App\Http\Resources\PaymentTransactionResource;
use App\Models\PaymentIntent;
use App\Services\Payments\CreatePaymentIntentService;
use App\Services\Payments\SimulatePaymentIntentService;
use Illuminate\Http\JsonResponse;

class PaymentIntentController extends Controller
{
    public function store(
        StorePaymentIntentRequest $request,
        CreatePaymentIntentService $createPaymentIntentService
    ): JsonResponse {
        $intent = $createPaymentIntentService->handle($request->validated());

        return response()->json([
            'message' => 'Payment intent created successfully.',
            'data' => new PaymentIntentResource($intent),
        ], 201);
    }

    public function show(PaymentIntent $paymentIntent): PaymentIntentResource
    {
        $paymentIntent->load([
            'attempts' => fn ($query) => $query->latest('attempt_number'),
            'transactions' => fn ($query) => $query->latest('id'),
        ]);

        return new PaymentIntentResource($paymentIntent);
    }

    public function simulate(
        SimulatePaymentIntentRequest $request,
        PaymentIntent $paymentIntent,
        SimulatePaymentIntentService $simulatePaymentIntentService
    ): JsonResponse {
        $result = $simulatePaymentIntentService->handle($paymentIntent, $request->validated());

        return response()->json([
            'message' => 'Payment intent simulated successfully.',
            'data' => [
                'intent' => new PaymentIntentResource($result['intent']->load([
                    'attempts' => fn ($query) => $query->latest('attempt_number'),
                    'transactions' => fn ($query) => $query->latest('id'),
                ])),
                'attempt' => new PaymentAttemptResource($result['attempt']),
                'transaction' => new PaymentTransactionResource($result['transaction']),
                'result' => $result['result'],
            ],
        ]);
    }
}
