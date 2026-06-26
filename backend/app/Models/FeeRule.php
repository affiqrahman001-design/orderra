<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class FeeRule extends Model
{
    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'fee_kind',
        'fulfillment_type',
        'calculation_type',
        'fixed_amount',
        'percentage_bps',
        'threshold_amount',
        'min_amount',
        'max_amount',
        'taxable',
        'conditions_json',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'taxable' => 'boolean',
        'is_active' => 'boolean',
        'conditions_json' => 'array',
    ];
}
