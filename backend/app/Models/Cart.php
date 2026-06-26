<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CartStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Cart extends Model
{
    protected $fillable = [
        'public_id',
        'cart_token',
        'user_id',
        'status',
        'currency',
        'fulfillment_type',
        'source',
        'promo_code',
        'promo_payload',
        'tip_type',
        'tip_value',
        'customer_context',
        'fulfillment_context',
        'pricing_snapshot',
        'last_priced_at',
        'expires_at',
    ];

    protected $casts = [
        'promo_payload' => 'array',
        'customer_context' => 'array',
        'fulfillment_context' => 'array',
        'pricing_snapshot' => 'array',
        'last_priced_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('sort_order');
    }

    public function placedOrder(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isDraft(): bool
    {
        return $this->status === CartStatus::CART_DRAFT->value;
    }

    public function isPlaced(): bool
    {
        return $this->placedOrder()->exists();
    }
}
