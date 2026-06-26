<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SplitBillAllocation extends Model
{
    protected $fillable = [
        'split_bill_plan_id',
        'split_bill_participant_id',
        'order_id',
        'order_item_id',
        'allocation_type',
        'quantity',
        'item_name',
        'item_slug',
        'subtotal_amount',
        'source_snapshot',
        'meta',
    ];

    protected $casts = [
        'source_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function splitBillPlan(): BelongsTo
    {
        return $this->belongsTo(SplitBillPlan::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(SplitBillParticipant::class, 'split_bill_participant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
