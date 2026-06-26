<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Orders\OrderController;
use App\Http\Controllers\Api\V1\Customer\CatalogCategoryController;
use App\Http\Controllers\Api\V1\Customer\CatalogItemController;
use App\Http\Controllers\Api\V1\Customer\CatalogOverviewController;
use App\Http\Controllers\Api\V1\Customer\DineInSessionController;
use App\Http\Controllers\Api\V1\Customer\PaymentIntentController;
use App\Http\Controllers\Api\V1\Customer\PricingQuoteController;
use App\Http\Controllers\Api\V1\Customer\PromoController;
use App\Http\Controllers\Api\V1\Customer\RefundController;
use App\Http\Controllers\Api\V1\Customer\SplitBillController;
use App\Http\Controllers\Api\V1\Customer\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/customer/ping', function () {
    return response()->json([
        'data' => [
            'ok' => true,
            'scope' => 'customer',
        ],
    ]);
});

Route::prefix('catalog')->group(function (): void {
    Route::get('/', CatalogOverviewController::class);
    Route::get('/categories', CatalogCategoryController::class);
    Route::get('/items', [CatalogItemController::class, 'index']);
    Route::get('/items/{item}', [CatalogItemController::class, 'show']);
});

Route::prefix('promos')->group(function (): void {
    Route::get('/', [PromoController::class, 'index']);
    Route::post('/validate', [PromoController::class, 'validate']);
});

Route::prefix('cart')->group(function (): void {
    Route::post('/', [CartController::class, 'store']);
    Route::get('/', [CartController::class, 'show']);

    Route::post('/lines', [CartController::class, 'storeLine']);
    Route::patch('/lines/{lineId}', [CartController::class, 'updateLine'])
        ->whereNumber('lineId');
    Route::delete('/lines/{lineId}', [CartController::class, 'destroyLine'])
        ->whereNumber('lineId');

    Route::patch('/fulfillment', [CartController::class, 'updateFulfillment']);
    Route::patch('/tip', [CartController::class, 'updateTip']);
    Route::patch('/promo', [CartController::class, 'updatePromo']);

    Route::get('/{cartToken}', [CartController::class, 'showByToken'])
        ->whereUuid('cartToken');
});

Route::post('/pricing/quote', PricingQuoteController::class);

Route::post('/checkout', [OrderController::class, 'store']);

Route::prefix('orders')->group(function (): void {
    Route::post('/', [OrderController::class, 'store']);

    Route::get('/{orderPublicId}/timeline', [OrderController::class, 'timeline']);
    Route::get('/{orderPublicId}', [OrderController::class, 'show']);

    Route::post('/{order:public_id}/refunds', [RefundController::class, 'store'])
        ->whereUuid('order');
});

Route::prefix('refunds')->group(function (): void {
    Route::post('/', [RefundController::class, 'storeStandalone']);

    Route::get('/{refund:public_id}', [RefundController::class, 'show'])
        ->whereUuid('refund');
});

Route::prefix('payments')->group(function (): void {
    Route::post('/intents', [PaymentIntentController::class, 'store']);

    Route::get('/intents/{paymentIntent:public_id}', [PaymentIntentController::class, 'show'])
        ->whereUuid('paymentIntent');

    Route::post('/intents/{paymentIntent:public_id}/simulate', [PaymentIntentController::class, 'simulate'])
        ->middleware('throttle:payment-simulation')
        ->whereUuid('paymentIntent');
});

Route::prefix('dine-in')->group(function (): void {
    Route::post('/qr-sessions', [DineInSessionController::class, 'open']);
    Route::post('/sessions/open', [DineInSessionController::class, 'open']);

    Route::get('/qr-sessions/by-code/{sessionCode}', [DineInSessionController::class, 'showByCode'])
        ->where('sessionCode', '[A-Za-z0-9\-]+');

    Route::get('/sessions/{qrSession:public_id}', [DineInSessionController::class, 'show'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/attach-cart', [DineInSessionController::class, 'attachCart'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/call-waiter', [DineInSessionController::class, 'callWaiter'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/request-bill', [DineInSessionController::class, 'requestBill'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/expire', [DineInSessionController::class, 'expire'])
        ->whereUuid('qrSession');

    Route::post('/split-bills', [SplitBillController::class, 'storeStandalone']);

    Route::get('/sessions/{qrSession:public_id}/split-bill', [SplitBillController::class, 'show'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/split-bill', [SplitBillController::class, 'store'])
        ->whereUuid('qrSession');

    Route::post('/sessions/{qrSession:public_id}/split-bill/{splitBillPlan:public_id}/finalize', [SplitBillController::class, 'finalize'])
        ->whereUuid('qrSession')
        ->whereUuid('splitBillPlan');
});

/** Short public resolver for printed QR collateral (aliases dine-in resolver). */
Route::get('/qr/{sessionCode}', [DineInSessionController::class, 'showByCode'])
    ->where('sessionCode', '[A-Za-z0-9\-]+');

Route::prefix('support')->group(function (): void {
    Route::post('/tickets', [SupportTicketController::class, 'store']);

    Route::get('/tickets/{supportTicket:public_id}', [SupportTicketController::class, 'show'])
        ->whereUuid('supportTicket');
});
