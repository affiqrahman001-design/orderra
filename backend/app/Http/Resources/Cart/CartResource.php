<?php

declare(strict_types=1);

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = (array) ($this->pricing_snapshot ?? []);

        return [
            'data' => [
                'id' => $this->public_id,
                'cart_token' => $this->cart_token,
                'status' => $this->status,
                'currency' => $this->currency,
                'fulfillment_type' => $this->fulfillment_type,
                'promo_code' => $this->promo_code,
                'tip_type' => $this->tip_type,
                'tip_value' => (int) $this->tip_value,
                'lines' => $this->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'item_name' => $line->item_name,
                    'item_slug' => $line->item_slug,
                    'image_url' => ((array) ($line->item_snapshot ?? []))['image_url'] ?? null,
                    'quantity' => $line->quantity,
                    'note' => $line->note,
                    'modifier_snapshot' => $line->modifier_snapshot ?? [],
                    'unit_price' => $this->toMoney($line->unit_price_amount),
                    'line_subtotal' => $this->toMoney($line->line_subtotal_amount),
                ])->values(),
                'totals' => [
                    'subtotal' => $this->toMoney($snapshot['subtotal'] ?? 0),
                    'discount' => $this->toMoney($snapshot['discount'] ?? 0),
                    'service_fee' => $this->toMoney($snapshot['service_fee'] ?? 0),
                    'delivery_fee' => $this->toMoney($snapshot['delivery_fee'] ?? 0),
                    'small_order_fee' => $this->toMoney($snapshot['small_order_fee'] ?? 0),
                    'tax' => $this->toMoney($snapshot['tax'] ?? 0),
                    'tip' => $this->toMoney($snapshot['tip'] ?? 0),
                    'total' => $this->toMoney($snapshot['total'] ?? 0),
                ],
                'pricing_meta' => $snapshot['meta'] ?? [],
                'fulfillment_context' => $this->fulfillment_context ?? [],
                'customer_context' => $this->customer_context ?? [],
            ],
        ];
    }

    private function toMoney(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
