<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethodCode;
use App\Enums\PaymentProviderCode;
use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_intent_id',
        'payment_attempt_id',
        'transaction_type',
        'direction',
        'status',
        'method_code',
        'provider_code',
        'currency',
        'amount',
        'provider_reference',
        'external_reference',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'method_code' => PaymentMethodCode::class,
            'provider_code' => PaymentProviderCode::class,
            'status' => PaymentTransactionStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest('id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentTransactionStatus::SUCCEEDED;
    }
}
