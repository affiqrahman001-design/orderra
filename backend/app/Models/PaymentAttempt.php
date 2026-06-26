<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\PaymentProviderCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_intent_id',
        'attempt_number',
        'method_code',
        'provider_code',
        'status',
        'amount',
        'simulation_outcome',
        'provider_reference',
        'request_payload',
        'response_payload',
        'meta',
        'error_code',
        'error_message',
        'initiated_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'method_code' => PaymentMethodCode::class,
            'provider_code' => PaymentProviderCode::class,
            'status' => PaymentAttemptStatus::class,
            'amount' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'meta' => 'array',
            'initiated_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function hasFailed(): bool
    {
        return $this->status === PaymentAttemptStatus::FAILED;
    }

    public function hasSucceeded(): bool
    {
        return $this->status === PaymentAttemptStatus::SUCCEEDED;
    }
}
