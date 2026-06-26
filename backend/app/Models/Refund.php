<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'order_id',
        'payment_intent_id',
        'payment_transaction_id',
        'category',
        'status',
        'resolution_type',
        'currency',
        'requested_amount',
        'approved_amount',
        'resolved_amount',
        'reason',
        'policy_snapshot',
        'context_snapshot',
        'notes',
        'requested_by_type',
        'requested_by_id',
        'requested_at',
        'reviewed_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'integer',
            'approved_amount' => 'integer',
            'resolved_amount' => 'integer',
            'policy_snapshot' => 'array',
            'context_snapshot' => 'array',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RefundEvent::class)->orderBy('id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class)->latest('id');
    }
}
