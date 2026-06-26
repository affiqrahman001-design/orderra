<?php

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
