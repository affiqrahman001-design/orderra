<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Promo extends Model
{
    protected $fillable = [
        'public_id',
        'code',
        'title',
        'description',
        'discount_type',
        'value_bps',
        'fixed_amount',
        'minimum_subtotal_amount',
        'badge_label',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_user_limit',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];
}
