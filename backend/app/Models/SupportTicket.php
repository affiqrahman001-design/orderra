<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupportTicket extends Model
{
    protected $fillable = [
        'public_id',
        'order_id',
        'refund_id',
        'payment_intent_id',
        'delivery_assignment_id',
        'category',
        'status',
        'subject',
        'description',
        'resolution_summary',
        'contact_snapshot',
        'meta',
        'opened_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'contact_snapshot' => 'array',
        'meta' => 'array',
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupportTicketEvent::class)->orderBy('id');
    }

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class);
    }
}
