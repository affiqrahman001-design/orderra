<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaxRule extends Model
{
    protected $fillable = [
        'branch_id',
        'country_code',
        'state_code',
        'city_code',
        'fulfillment_type',
        'name',
        'rate_bps',
        'applies_to_subtotal',
        'applies_to_service_fee',
        'applies_to_delivery_fee',
        'applies_to_small_order_fee',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'applies_to_subtotal' => 'boolean',
        'applies_to_service_fee' => 'boolean',
        'applies_to_delivery_fee' => 'boolean',
        'applies_to_small_order_fee' => 'boolean',
        'is_active' => 'boolean',
    ];
}
