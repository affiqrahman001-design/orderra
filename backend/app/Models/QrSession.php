<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class QrSession extends Model
{
    protected $fillable = [
        'public_id',
        'restaurant_table_id',
        'session_code',
        'status',
        'party_size',
        'opened_by_user_id',
        'opened_via',
        'meta',
        'opened_at',
        'last_activity_at',
        'bill_requested_at',
        'closed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'opened_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'bill_requested_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QrSessionEvent::class)->latest('id');
    }

    public function cartLinks(): HasMany
    {
        return $this->hasMany(QrSessionCart::class)->latest('id');
    }

    public function orderLinks(): HasMany
    {
        return $this->hasMany(QrSessionOrder::class)->latest('id');
    }

    public function carts(): BelongsToMany
    {
        return $this->belongsToMany(Cart::class, 'qr_session_carts')
            ->withTimestamps()
            ->withPivot(['linked_at']);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'qr_session_orders')
            ->withTimestamps()
            ->withPivot(['linked_at', 'linked_from_cart_id']);
    }

    public function isActive(): bool
    {
        return in_array($this->status, config('dine_in.qr_sessions.active_statuses', []), true);
    }

    public function splitBillPlans(): HasMany
    {
        return $this->hasMany(SplitBillPlan::class)->latest('id');
    }

    public function latestActiveSplitBillPlan(): HasOne
    {
        return $this->hasOne(SplitBillPlan::class)
            ->whereIn('status', ['draft', 'finalized'])
            ->latestOfMany();
    }
}
