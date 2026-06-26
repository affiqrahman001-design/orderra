<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DeliveryTrackingEvent extends Model
{
    protected $fillable = [
        'delivery_assignment_id',
        'status',
        'eta_minutes',
        'simulated_latitude',
        'simulated_longitude',
        'note',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'eta_minutes' => 'integer',
        'simulated_latitude' => 'float',
        'simulated_longitude' => 'float',
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class);
    }
}
