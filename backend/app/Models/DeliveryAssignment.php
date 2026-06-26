<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DeliveryAssignment extends Model
{
    protected $fillable = [
        'public_id',
        'order_id',
        'rider_id',
        'provider_type',
        'status',
        'eta_minutes',
        'context_snapshot',
        'meta',
        'assigned_at',
        'picked_up_at',
        'near_customer_at',
        'delivered_at',
    ];

    protected $casts = [
        'eta_minutes' => 'integer',
        'context_snapshot' => 'array',
        'meta' => 'array',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'near_customer_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(DeliveryTrackingEvent::class)->orderBy('id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class)->latest('id');
    }
}
