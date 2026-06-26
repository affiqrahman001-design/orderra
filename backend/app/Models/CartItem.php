<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'menu_item_id',
        'variant_id',
        'item_name',
        'item_slug',
        'item_snapshot',
        'modifier_snapshot',
        'quantity',
        'unit_base_amount',
        'unit_modifier_amount',
        'unit_price_amount',
        'line_subtotal_amount',
        'note',
        'sort_order',
    ];

    protected $casts = [
        'item_snapshot' => 'array',
        'modifier_snapshot' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
