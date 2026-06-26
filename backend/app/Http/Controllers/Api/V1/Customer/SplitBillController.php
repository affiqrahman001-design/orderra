<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\DineIn\CreateSplitBillPlanRequest;
use App\Http\Requests\DineIn\StoreStandaloneSplitBillPlanRequest;
use App\Http\Resources\DineIn\SplitBillPlanResource;
use App\Models\QrSession;
use App\Models\SplitBillPlan;
use App\Services\DineIn\SplitBillService;
use Illuminate\Support\Arr;

final class SplitBillController extends Controller
{
    public function __construct(
        private readonly SplitBillService $splitBillService,
    ) {}

    public function show(QrSession $qrSession): SplitBillPlanResource
    {
        return new SplitBillPlanResource(
            $this->splitBillService->showActive($qrSession)
        );
    }

    public function store(CreateSplitBillPlanRequest $request, QrSession $qrSession): SplitBillPlanResource
    {
        return new SplitBillPlanResource(
            $this->splitBillService->createDraft($qrSession, $request->validated())
        );
    }

    public function storeStandalone(StoreStandaloneSplitBillPlanRequest $request): SplitBillPlanResource
    {
        $validated = $request->validated();

        $qrSession = QrSession::query()
            ->where('public_id', $validated['qr_session_id'])
            ->firstOrFail();

        return new SplitBillPlanResource(
            $this->splitBillService->createDraft(
                $qrSession,
                Arr::except($validated, ['qr_session_id'])
            )
        );
    }

    public function finalize(QrSession $qrSession, SplitBillPlan $splitBillPlan): SplitBillPlanResource
    {
        return new SplitBillPlanResource(
            $this->splitBillService->finalize($qrSession, $splitBillPlan)
        );
    }
}
