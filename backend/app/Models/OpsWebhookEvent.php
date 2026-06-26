<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OpsWebhookEvent extends Model
{
    protected $fillable = [
        'public_id',
        'event_name',
        'aggregate_type',
        'status',
        'order_id',
        'refund_id',
        'payment_intent_id',
        'delivery_assignment_id',
        'payload',
        'headers',
        'notes',
        'generated_at',
        'last_replayed_at',
        'replay_count',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'generated_at' => 'datetime',
        'last_replayed_at' => 'datetime',
        'replay_count' => 'integer',
        'failed_at' => 'datetime',
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

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class);
    }
}
