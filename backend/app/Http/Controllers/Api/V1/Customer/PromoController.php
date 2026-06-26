<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Services\Promos\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PromoController extends Controller
{
    public function __construct(
        private readonly PromoCodeService $promoCodeService,
    ) {}

    public function index(): JsonResponse
    {
        $promos = $this->promoCodeService->activePromos();

        return response()->json([
            'data' => $promos->map(fn (Promo $promo) => [
                'code' => $promo->code,
                'title' => $promo->title,
                'description' => $promo->description,
                'discountType' => $promo->discount_type,
                'value' => $promo->discount_type === 'fixed'
                  ? round(((int) $promo->fixed_amount) / 100, 2)
                  : round(((int) $promo->value_bps) / 100, 2),
                'minimumSubtotal' => $promo->minimum_subtotal_amount !== null
                  ? round(((int) $promo->minimum_subtotal_amount) / 100, 2)
                  : null,
                'badge' => $promo->badge_label,
            ])->values(),
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(
            $this->promoCodeService->validateCode(
                (string) $validated['code'],
                (int) round(((float) $validated['subtotal']) * 100),
            )
        );
    }
}
