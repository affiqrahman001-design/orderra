<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\AdminBranchController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminDeliveryAssignmentController;
use App\Http\Controllers\Api\V1\Admin\AdminDeliveryZoneController;
use App\Http\Controllers\Api\V1\Admin\AdminDemoScenarioController;
use App\Http\Controllers\Api\V1\Admin\AdminFeeRuleController;
use App\Http\Controllers\Api\V1\Admin\AdminMenuCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminMenuItemController;
use App\Http\Controllers\Api\V1\Admin\AdminModifierGroupController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationLogController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentLogController;
use App\Http\Controllers\Api\V1\Admin\AdminPromoController;
use App\Http\Controllers\Api\V1\Admin\AdminQrSessionController;
use App\Http\Controllers\Api\V1\Admin\AdminRefundController;
use App\Http\Controllers\Api\V1\Admin\AdminRestaurantTableController;
use App\Http\Controllers\Api\V1\Admin\AdminTaxRuleController;
use App\Http\Controllers\Api\V1\Admin\AdminWebhookViewerController;
use App\Http\Controllers\Api\V1\Admin\RefundReviewController;
use App\Http\Controllers\Api\V1\Admin\SupportTicketAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AdminDashboardController::class);
Route::get('/demo-scenarios', AdminDemoScenarioController::class);

Route::prefix('orders')->group(function (): void {
    Route::get('/', [AdminOrderController::class, 'index']);
    Route::get('/{orderPublicId}', [AdminOrderController::class, 'show']);
    Route::post('/{orderPublicId}/status', [AdminOrderController::class, 'transition']);
});

Route::prefix('payments')->group(function (): void {
    Route::get('/intents', [AdminPaymentLogController::class, 'index']);
    Route::get('/intents/{paymentIntent:public_id}', [AdminPaymentLogController::class, 'show'])
        ->whereUuid('paymentIntent');
    Route::get('/attempts', [AdminPaymentLogController::class, 'attempts']);
    Route::get('/attempts/{paymentAttempt}', [AdminPaymentLogController::class, 'showAttempt'])
        ->whereNumber('paymentAttempt');
    Route::get('/transactions', [AdminPaymentLogController::class, 'transactions']);
    Route::get('/transactions/{paymentTransaction}', [AdminPaymentLogController::class, 'showTransaction'])
        ->whereNumber('paymentTransaction');
});

Route::prefix('refunds')->group(function (): void {
    Route::get('/', [AdminRefundController::class, 'index']);
    Route::get('/{refund:public_id}', [AdminRefundController::class, 'show'])
        ->whereUuid('refund');
    Route::post('/{refund:public_id}/review', RefundReviewController::class)
        ->whereUuid('refund');
});

Route::prefix('webhooks')->group(function (): void {
    Route::get('/', [AdminWebhookViewerController::class, 'index']);
    Route::get('/{opsWebhookEvent:public_id}', [AdminWebhookViewerController::class, 'show'])
        ->whereUuid('opsWebhookEvent');
});

Route::prefix('riders')->group(function (): void {
    Route::get('/', [AdminDeliveryAssignmentController::class, 'riders']);
    Route::get('/pool', [AdminDeliveryAssignmentController::class, 'riderPool']);
    Route::get('/assignments', [AdminDeliveryAssignmentController::class, 'index']);
    Route::post('/orders/{order:public_id}/assignments', [AdminDeliveryAssignmentController::class, 'assign'])
        ->whereUuid('order');
    Route::get('/assignments/{deliveryAssignment:public_id}', [AdminDeliveryAssignmentController::class, 'show'])
        ->whereUuid('deliveryAssignment');
    Route::post('/assignments/{deliveryAssignment:public_id}/advance', [AdminDeliveryAssignmentController::class, 'advance'])
        ->whereUuid('deliveryAssignment');
});

Route::prefix('tables')->group(function (): void {
    Route::get('/', [AdminRestaurantTableController::class, 'index']);
    Route::post('/{tablePublicId}/qr-session', [AdminRestaurantTableController::class, 'rotateQr']);
});

Route::prefix('dine-in')->group(function (): void {
    Route::get('/sessions', [AdminQrSessionController::class, 'index']);
    Route::get('/sessions/{qrSessionId}', [AdminQrSessionController::class, 'show']);
});

Route::prefix('support')->group(function (): void {
    Route::get('/tickets', [SupportTicketAdminController::class, 'index']);
    Route::get('/tickets/{supportTicket:public_id}', [SupportTicketAdminController::class, 'show'])
        ->whereUuid('supportTicket');
    Route::post('/tickets/{supportTicket:public_id}/transition', [SupportTicketAdminController::class, 'transition'])
        ->whereUuid('supportTicket');
});

Route::prefix('audit-logs')->group(function (): void {
    Route::get('/', [AdminAuditLogController::class, 'index']);
    Route::get('/{auditLog:public_id}', [AdminAuditLogController::class, 'show'])
        ->whereUuid('auditLog');
});

Route::prefix('notification-logs')->group(function (): void {
    Route::get('/', [AdminNotificationLogController::class, 'index']);
    Route::get('/{notificationLogId}', [AdminNotificationLogController::class, 'show']);
});

Route::prefix('branches')->group(function (): void {
    Route::get('/', [AdminBranchController::class, 'index']);
    Route::get('/{branchId}', [AdminBranchController::class, 'show']);
    Route::post('/', [AdminBranchController::class, 'store']);
    Route::patch('/{branchId}', [AdminBranchController::class, 'update']);
});

Route::prefix('delivery-zones')->group(function (): void {
    Route::get('/', [AdminDeliveryZoneController::class, 'index']);
    Route::get('/{deliveryZoneId}', [AdminDeliveryZoneController::class, 'show']);
    Route::post('/', [AdminDeliveryZoneController::class, 'store']);
    Route::patch('/{deliveryZoneId}', [AdminDeliveryZoneController::class, 'update']);
});

Route::prefix('tax-rules')->group(function (): void {
    Route::get('/', [AdminTaxRuleController::class, 'index']);
    Route::get('/{taxRuleId}', [AdminTaxRuleController::class, 'show']);
    Route::post('/', [AdminTaxRuleController::class, 'store']);
    Route::patch('/{taxRuleId}', [AdminTaxRuleController::class, 'update']);
});

Route::prefix('fee-rules')->group(function (): void {
    Route::get('/', [AdminFeeRuleController::class, 'index']);
    Route::get('/{feeRuleId}', [AdminFeeRuleController::class, 'show']);
    Route::post('/', [AdminFeeRuleController::class, 'store']);
    Route::patch('/{feeRuleId}', [AdminFeeRuleController::class, 'update']);
});

Route::prefix('menu-categories')->group(function (): void {
    Route::get('/', [AdminMenuCategoryController::class, 'index']);
    Route::post('/', [AdminMenuCategoryController::class, 'store']);
    Route::get('/{categoryId}', [AdminMenuCategoryController::class, 'show']);
    Route::patch('/{categoryId}', [AdminMenuCategoryController::class, 'update']);
});

Route::prefix('menu-items')->group(function (): void {
    Route::get('/', [AdminMenuItemController::class, 'index']);
    Route::post('/', [AdminMenuItemController::class, 'store']);
    Route::get('/{itemId}', [AdminMenuItemController::class, 'show']);
    Route::patch('/{itemId}', [AdminMenuItemController::class, 'update']);
});

Route::prefix('promos')->group(function (): void {
    Route::get('/', [AdminPromoController::class, 'index']);
    Route::post('/', [AdminPromoController::class, 'store']);
    Route::get('/{promoId}', [AdminPromoController::class, 'show']);
    Route::patch('/{promoId}', [AdminPromoController::class, 'update']);
});

Route::prefix('modifier-groups')->group(function (): void {
    Route::get('/', [AdminModifierGroupController::class, 'index']);
    Route::post('/', [AdminModifierGroupController::class, 'store']);
    Route::get('/{groupId}', [AdminModifierGroupController::class, 'show']);
    Route::patch('/{groupId}', [AdminModifierGroupController::class, 'update']);
});
