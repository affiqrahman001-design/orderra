<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderFulfillment extends Model
{
    protected $fillable = [
        'order_id',
        'branch_id',
        'fulfillment_type',
        'contact_name',
        'contact_phone',
        'scheduled_for',
        'eta_minutes',
        'pickup_code',
        'table_label',
        'party_size',
        'address_snapshot',
        'context_snapshot',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'address_snapshot' => 'array',
        'context_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
