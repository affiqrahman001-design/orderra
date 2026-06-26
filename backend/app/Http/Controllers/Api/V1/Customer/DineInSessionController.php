<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\DineIn\AttachCartToSessionRequest;
use App\Http\Requests\DineIn\CallWaiterRequest;
use App\Http\Requests\DineIn\OpenDineInSessionRequest;
use App\Http\Requests\DineIn\RequestBillRequest;
use App\Http\Resources\DineIn\QrSessionResource;
use App\Models\QrSession;
use App\Services\DineIn\DineInSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DineInSessionController extends Controller
{
    public function __construct(
        private readonly DineInSessionService $dineInSessionService,
    ) {}

    public function open(OpenDineInSessionRequest $request): QrSessionResource
    {
        $session = $this->dineInSessionService->open($request->validated());

        return new QrSessionResource($session);
    }

    public function showByCode(Request $request, string $sessionCode): JsonResponse
    {
        $normalized = mb_strtoupper(trim($sessionCode), 'UTF-8');

        /** @var QrSession $session */
        $session = QrSession::query()
            ->where('session_code', $normalized)
            ->firstOrFail();

        $resolved = $this->dineInSessionService->show($session);

        return response()->json(
            (new QrSessionResource($resolved))->resolve($request),
        );
    }

    public function show(QrSession $qrSession): QrSessionResource
    {
        return new QrSessionResource(
            $this->dineInSessionService->show($qrSession)
        );
    }

    public function attachCart(AttachCartToSessionRequest $request, QrSession $qrSession): QrSessionResource
    {
        $session = $this->dineInSessionService->attachCart(
            $qrSession,
            (string) $request->validated('cart_token'),
        );

        return new QrSessionResource($session);
    }

    public function callWaiter(CallWaiterRequest $request, QrSession $qrSession): QrSessionResource
    {
        $session = $this->dineInSessionService->callWaiter(
            $qrSession,
            $request->validated('note'),
        );

        return new QrSessionResource($session);
    }

    public function requestBill(RequestBillRequest $request, QrSession $qrSession): QrSessionResource
    {
        $session = $this->dineInSessionService->requestBill(
            $qrSession,
            $request->validated('note'),
        );

        return new QrSessionResource($session);
    }

    public function expire(RequestBillRequest $request, QrSession $qrSession): QrSessionResource
    {
        $session = $this->dineInSessionService->expire(
            $qrSession,
            $request->validated('note'),
        );

        return new QrSessionResource($session);
    }
}
