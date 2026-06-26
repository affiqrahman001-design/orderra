<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refunds\ReviewRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminNotificationLogger;
use App\Services\Refunds\ReviewRefundService;
use Illuminate\Http\JsonResponse;

final class RefundReviewController extends Controller
{
    public function __invoke(
        ReviewRefundRequest $request,
        Refund $refund,
        ReviewRefundService $reviewRefundService,
    ): JsonResponse {
        $refund = $reviewRefundService->handle(
            refund: $refund,
            payload: $request->validated(),
            actorType: 'admin',
            actorId: null,
        );

        $refund->loadMissing('order');

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'refund.review',
            entityType: 'refund',
            entityPublicId: $refund->public_id,
            entitySecondaryKey: $refund->category,
            summary: sprintf('Refund moved to %s.', $refund->status),
            requestSnapshot: [
                'decision' => (string) $request->validated('decision'),
                'notes' => $request->validated('notes'),
            ],
            contextSnapshot: [
                'category' => $refund->category,
                'status' => $refund->status,
                'resolution_type' => $refund->resolution_type,
                'currency' => $refund->currency,
            ],
        );

        app(AdminNotificationLogger::class)->logSimulated(
            channel: 'in_app',
            notificationType: 'refund_status_changed',
            recipientType: 'customer',
            recipientKey: data_get(optional($refund->order)->customer_context_snapshot, 'email')
            ?: data_get(optional($refund->order)->customer_context_snapshot, 'phone')
            ?: data_get(optional($refund->order)->customer_context_snapshot, 'name')
            ?: optional($refund->order)?->order_code,
            entityType: 'refund',
            entityPublicId: $refund->public_id,
            subject: 'Refund status updated',
            title: 'Refund '.$refund->public_id,
            bodyPreview: sprintf('Status refund telah dikemas kini ke %s.', $refund->status),
            meta: [
                'category' => $refund->category,
                'status' => $refund->status,
                'resolution_type' => $refund->resolution_type,
                'order_code' => optional($refund->order)?->order_code,
            ],
        );

        $resource = new RefundResource($refund);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Refund reviewed successfully.',
            'data' => $resolved['data'],
        ]);
    }
}
