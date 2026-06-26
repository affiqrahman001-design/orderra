<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\ModifierOption;
use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CartService
{
    public function __construct(
        private readonly PricingService $pricingService,
    ) {}

    public function resolve(?string $cartToken): Cart
    {
        if ($cartToken !== null && $cartToken !== '') {
            $cart = Cart::with(['lines', 'placedOrder'])->firstWhere('cart_token', $cartToken);

            if ($cart !== null && $cart->placedOrder === null) {
                return $this->reprice($cart);
            }
        }

        $cart = Cart::create([
            'public_id' => (string) Str::uuid(),
            'cart_token' => (string) Str::uuid(),
            'status' => config('cart.default_status'),
            'currency' => config('cart.defaults.currency'),
            'fulfillment_type' => config('cart.defaults.fulfillment_type'),
            'source' => config('cart.defaults.source'),
            'tip_type' => config('cart.defaults.tip_type'),
            'tip_value' => config('cart.defaults.tip_value'),
            'expires_at' => now()->addMinutes((int) config('cart.expiry_minutes', 240)),
        ]);

        return $this->reprice($cart);
    }

    public function addLine(Cart $cart, array $payload): Cart
    {
        $menuItem = $this->resolveMenuItem((string) $payload['menu_item_id']);
        $selectedOptions = $this->resolveSelectedModifierOptions($menuItem, $payload['selected_modifiers'] ?? []);

        $quantity = max(
            1,
            min(
                (int) config('cart.line_limits.max_quantity_per_line', 10),
                (int) ($payload['quantity'] ?? 1),
            ),
        );

        $unitBaseAmount = (int) $menuItem->base_price_amount;

        $unitModifierAmount = $selectedOptions->sum(
            fn (ModifierOption $option): int => (int) $option->price_delta_amount
        );

        $unitPriceAmount = $unitBaseAmount + $unitModifierAmount;
        $sortOrder = ((int) $cart->lines()->max('sort_order')) + 1;

        return DB::transaction(function () use ($cart, $payload, $menuItem, $selectedOptions, $quantity, $unitBaseAmount, $unitModifierAmount, $unitPriceAmount, $sortOrder): Cart {
            $cart->lines()->create([
                'menu_item_id' => $menuItem->id,
                'variant_id' => null,
                'item_name' => $menuItem->name,
                'item_slug' => $menuItem->slug,
                'item_snapshot' => $this->buildItemSnapshot($menuItem),
                'modifier_snapshot' => $this->buildModifierSnapshot($selectedOptions),
                'quantity' => $quantity,
                'unit_base_amount' => $unitBaseAmount,
                'unit_modifier_amount' => $unitModifierAmount,
                'unit_price_amount' => $unitPriceAmount,
                'line_subtotal_amount' => $unitPriceAmount * $quantity,
                'note' => $payload['note'] ?? null,
                'sort_order' => $sortOrder,
            ]);

            return $this->reprice($cart->refresh());
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): Cart
    {
        /** @var CartItem $line */
        $line = $cart->lines()->findOrFail($lineId);

        if ($quantity <= 0) {
            $line->delete();

            return $this->reprice($cart->refresh());
        }

        $quantity = min((int) config('cart.line_limits.max_quantity_per_line', 10), $quantity);

        $line->update([
            'quantity' => $quantity,
            'line_subtotal_amount' => $line->unit_price_amount * $quantity,
        ]);

        return $this->reprice($cart->refresh());
    }

    public function removeLine(Cart $cart, int $lineId): Cart
    {
        $cart->lines()->whereKey($lineId)->delete();

        return $this->reprice($cart->refresh());
    }

    public function updateFulfillment(Cart $cart, array $payload): Cart
    {
        $fulfillmentType = (string) $payload['fulfillment_type'];

        $cart->update([
            'fulfillment_type' => $fulfillmentType,
            'fulfillment_context' => $this->normalizeFulfillmentContext(
                $fulfillmentType,
                (array) ($payload['fulfillment_context'] ?? []),
            ),
            'customer_context' => $this->normalizeCustomerContext(
                (array) ($payload['customer_context'] ?? []),
            ),
        ]);

        return $this->reprice($cart->refresh());
    }

    public function updateTip(Cart $cart, array $payload): Cart
    {
        $cart->update([
            'tip_type' => $payload['tip_type'],
            'tip_value' => (int) $payload['tip_value'],
        ]);

        return $this->reprice($cart->refresh());
    }

    public function updatePromo(Cart $cart, ?string $promoCode): Cart
    {
        $cart->update([
            'promo_code' => $promoCode !== null ? strtoupper(trim($promoCode)) : null,
        ]);

        return $this->reprice($cart->refresh());
    }

    public function reprice(Cart $cart): Cart
    {
        $snapshot = $this->pricingService->calculateForCart($cart);

        $cart->update([
            'currency' => $snapshot['currency'],
            'pricing_snapshot' => $snapshot,
            'last_priced_at' => now(),
        ]);

        return $cart->fresh('lines');
    }

    private function normalizeCustomerContext(array $context): array
    {
        if (isset($context['full_name']) && ! isset($context['name'])) {
            $context['name'] = $context['full_name'];
        }

        return array_filter($context, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function normalizeFulfillmentContext(string $fulfillmentType, array $context): array
    {
        if ($fulfillmentType === 'delivery') {
            $context['address_line1'] ??= $context['address'] ?? null;
            $context['state'] ??= $context['state_code'] ?? 'NY';
            $context['country_code'] ??= 'US';
            $context['address_line2'] ??= $context['apartment'] ?? null;
        }

        if ($fulfillmentType === 'dine_in') {
            $context['table_label'] ??= $context['table_reference'] ?? $context['table'] ?? null;
        }

        return array_filter($context, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function resolveMenuItem(string $publicId): MenuItem
    {
        $menuItem = MenuItem::query()
            ->with(['category', 'modifierGroups.options'])
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->first();

        if (! $menuItem) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'Menu item tidak dijumpai atau tidak aktif.',
            ]);
        }

        return $menuItem;
    }

    /**
     * @param  array<int,array<string,mixed>>  $selectedModifiers
     * @return Collection<int, ModifierOption>
     */
    private function resolveSelectedModifierOptions(MenuItem $menuItem, array $selectedModifiers): Collection
    {
        $optionPublicIds = collect($selectedModifiers)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && trim($id) !== '')
            ->map(fn (string $id): string => trim($id))
            ->unique()
            ->values();

        if ($optionPublicIds->isEmpty()) {
            return new Collection;
        }

        $options = ModifierOption::query()
            ->with('modifierGroup')
            ->whereIn('public_id', $optionPublicIds->all())
            ->where('is_active', true)
            ->get();

        if ($options->count() !== $optionPublicIds->count()) {
            throw ValidationException::withMessages([
                'selected_modifiers' => 'Ada modifier option yang tidak dijumpai atau tidak aktif.',
            ]);
        }

        $invalidOption = $options->first(function (ModifierOption $option) use ($menuItem): bool {
            return (int) $option->modifierGroup?->menu_item_id !== (int) $menuItem->id
              || (bool) $option->modifierGroup?->is_active !== true;
        });

        if ($invalidOption) {
            throw ValidationException::withMessages([
                'selected_modifiers' => 'Modifier option tidak sah untuk menu item ini.',
            ]);
        }

        return $options;
    }

    private function buildItemSnapshot(MenuItem $menuItem): array
    {
        return [
            'id' => $menuItem->public_id,
            'internal_id' => $menuItem->id,
            'code' => $menuItem->code,
            'name' => $menuItem->name,
            'short_name' => $menuItem->short_name,
            'slug' => $menuItem->slug,
            'description' => $menuItem->description,
            'category_slug' => $menuItem->category?->slug,
            'currency' => $menuItem->currency,
            'base_price_amount' => (int) $menuItem->base_price_amount,
            'image_url' => $menuItem->image_url,
            'badge_label' => $menuItem->badge_label,
            'prep_note' => $menuItem->prep_note,
            'product_flow' => $menuItem->product_flow,
        ];
    }

    /**
     * @param  Collection<int, ModifierOption>  $options
     */
    private function buildModifierSnapshot(Collection $options): array
    {
        return $options
            ->map(fn (ModifierOption $option): array => [
                'id' => $option->public_id,
                'internal_id' => $option->id,
                'code' => $option->code,
                'label' => $option->label,
                'price_delta_amount' => (int) $option->price_delta_amount,
                'group' => [
                    'id' => $option->modifierGroup?->public_id,
                    'internal_id' => $option->modifierGroup?->id,
                    'code' => $option->modifierGroup?->code,
                    'name' => $option->modifierGroup?->name,
                    'selection_mode' => $option->modifierGroup?->selection_mode,
                ],
            ])
            ->values()
            ->all();
    }
}
