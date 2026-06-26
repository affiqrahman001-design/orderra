<?php

namespace App\Enums;

enum PaymentIntentStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case AUTHORIZED = 'authorized';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::SUCCEEDED,
            self::FAILED,
            self::CANCELLED,
            self::EXPIRED,
        ], true);
    }
}
