<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class DeliveryZone extends Model
{
    protected $fillable = [
        'public_id',
        'branch_code',
        'code',
        'name',
        'status',
        'pricing_strategy',
        'minimum_order_amount',
        'base_fee_amount',
        'fee_per_km_amount',
        'free_delivery_threshold_amount',
        'estimated_minutes',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $zone): void {
            if (! $zone->public_id) {
                $zone->public_id = (string) Str::uuid();
            }
        });
    }
}
