<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SplitBillParticipant extends Model
{
    protected $fillable = [
        'public_id',
        'split_bill_plan_id',
        'display_name',
        'seat_label',
        'participant_order',
        'is_primary_payer',
        'status',
        'subtotal_amount',
        'discount_amount',
        'service_fee_amount',
        'delivery_fee_amount',
        'small_order_fee_amount',
        'tax_amount',
        'tip_amount',
        'total_amount',
        'meta',
    ];

    protected $casts = [
        'is_primary_payer' => 'boolean',
        'meta' => 'array',
    ];

    public function splitBillPlan(): BelongsTo
    {
        return $this->belongsTo(SplitBillPlan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SplitBillAllocation::class, 'split_bill_participant_id');
    }
}
