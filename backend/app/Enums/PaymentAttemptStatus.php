<?php

namespace App\Enums;

enum PaymentAttemptStatus: string
{
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

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
        ], true);
    }
}
