<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartLineRequest;
use App\Http\Requests\Cart\UpdateCartFulfillmentRequest;
use App\Http\Requests\Cart\UpdateCartTipRequest;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    private function respondWithCart(Request $request, Cart $cart): JsonResponse
    {
        $resolved = (new CartResource($cart))->resolve($request);

        return response()
            ->json($resolved)
            ->header(config('cart.token_header'), $cart->cart_token);
    }

    public function store(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve(null);

        return $this->respondWithCart($request, $cart)->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        return $this->respondWithCart($request, $cart);
    }

    public function showByToken(Request $request, string $cartToken): JsonResponse
    {
        $cart = Cart::query()
            ->with(['lines', 'placedOrder'])
            ->where('cart_token', $cartToken)
            ->firstOrFail();

        if ($cart->placedOrder !== null) {
            throw ValidationException::withMessages([
                'cart_token' => ['This cart already completed checkout. Start a new cart.'],
            ]);
        }

        return $this->respondWithCart($request, $this->cartService->reprice($cart));
    }

    public function storeLine(StoreCartLineRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->addLine($cart, $request->validated());

        return $this->respondWithCart($request, $cart);
    }

    public function updateLine(Request $request, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->updateLineQuantity($cart, $lineId, (int) $validated['quantity']);

        return $this->respondWithCart($request, $cart);
    }

    public function destroyLine(Request $request, int $lineId): JsonResponse
    {
        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->removeLine($cart, $lineId);

        return $this->respondWithCart($request, $cart);
    }

    public function updateFulfillment(UpdateCartFulfillmentRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->updateFulfillment($cart, $request->validated());

        return $this->respondWithCart($request, $cart);
    }

    public function updateTip(UpdateCartTipRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->updateTip($cart, $request->validated());

        return $this->respondWithCart($request, $cart);
    }

    public function updatePromo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        $cart = $this->cartService->resolve(
            $request->header(config('cart.token_header'))
        );

        $cart = $this->cartService->updatePromo($cart, $validated['promo_code'] ?? null);

        return $this->respondWithCart($request, $cart);
    }
}
