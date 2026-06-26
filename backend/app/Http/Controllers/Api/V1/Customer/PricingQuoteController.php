<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PricingQuoteController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_token' => ['nullable', 'string', 'max:100'],
            'fulfillment_type' => ['nullable', 'string', 'in:delivery,pickup,dine_in'],
            'fulfillment_context' => ['nullable', 'array'],
            'customer_context' => ['nullable', 'array'],
            'tip_type' => ['nullable', 'string', 'in:none,fixed,percentage'],
            'tip_value' => ['nullable', 'integer', 'min:0'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        $cartToken = $validated['cart_token'] ?? $request->header(config('cart.token_header'));

        if ($cartToken === null || $cartToken === '') {
            throw ValidationException::withMessages([
                'cart_token' => 'Cart token is required for pricing quote.',
            ]);
        }

        $cart = Cart::query()
            ->with(['lines', 'placedOrder'])
            ->where('cart_token', $cartToken)
            ->firstOrFail();

        if ($cart->placedOrder !== null) {
            throw ValidationException::withMessages([
                'cart_token' => ['This cart already completed checkout. Start a new cart.'],
            ]);
        }

        if (isset($validated['fulfillment_type'])) {
            $cart = $this->cartService->updateFulfillment($cart, [
                'fulfillment_type' => $validated['fulfillment_type'],
                'fulfillment_context' => $validated['fulfillment_context'] ?? [],
                'customer_context' => $validated['customer_context'] ?? [],
            ]);
        }

        if (array_key_exists('promo_code', $validated)) {
            $cart = $this->cartService->updatePromo($cart, $validated['promo_code']);
        }

        if (isset($validated['tip_type'], $validated['tip_value'])) {
            $cart = $this->cartService->updateTip($cart, [
                'tip_type' => $validated['tip_type'],
                'tip_value' => (int) $validated['tip_value'],
            ]);
        }

        $repriced = $this->cartService->reprice($cart);
        $resolved = (new CartResource($repriced))->resolve($request);

        return response()->json($resolved)->header(config('cart.token_header'), $repriced->cart_token);
    }
}
