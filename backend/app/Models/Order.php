<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Order extends Model
{
    protected $fillable = [
        'public_id',
        'order_code',
        'cart_id',
        'user_id',
        'status',
        'currency',
        'fulfillment_type',
        'source',
        'customer_context_snapshot',
        'fulfillment_context_snapshot',
        'pricing_snapshot',
        'subtotal_amount',
        'discount_amount',
        'service_fee_amount',
        'delivery_fee_amount',
        'small_order_fee_amount',
        'tax_amount',
        'tip_amount',
        'total_amount',
        'meta',
        'placed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'customer_context_snapshot' => 'array',
        'fulfillment_context_snapshot' => 'array',
        'pricing_snapshot' => 'array',
        'meta' => 'array',
        'placed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class)->latest('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest('id');
    }

    public function allowedTransitions(): array
    {
        $common = (array) config("orders.transitions.common.{$this->status}", []);
        $mode = (array) config("orders.transitions.{$this->fulfillment_type}.{$this->status}", []);

        return array_values(array_unique(array_merge($common, $mode)));
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class)->latest('id');
    }

    public function deliveryAssignment(): HasOne
    {
        return $this->hasOne(DeliveryAssignment::class);
    }
}
