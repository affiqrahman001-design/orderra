<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\PaymentProviderCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'intent_code',
        'cart_id',
        'order_id',
        'user_id',
        'method_code',
        'provider_code',
        'status',
        'country_code',
        'currency',
        'amount',
        'branch_code',
        'simulation_context',
        'provider_context',
        'meta',
        'expires_at',
        'last_attempted_at',
        'authorized_at',
        'succeeded_at',
        'failed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'method_code' => PaymentMethodCode::class,
            'provider_code' => PaymentProviderCode::class,
            'status' => PaymentIntentStatus::class,
            'amount' => 'integer',
            'simulation_context' => 'array',
            'provider_context' => 'array',
            'meta' => 'array',
            'expires_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'authorized_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class)->orderBy('attempt_number');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest('id');
    }

    public function latestAttempt(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class)->latest('attempt_number');
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function isPendingLike(): bool
    {
        return in_array($this->status?->value, [
            PaymentIntentStatus::DRAFT->value,
            PaymentIntentStatus::PENDING->value,
            PaymentIntentStatus::PROCESSING->value,
        ], true);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class)->latest('id');
    }
}
