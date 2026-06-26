<?php

namespace App\Enums;

enum PaymentMethodCode: string
{
    case CARD = 'card';
    case APPLE_PAY = 'apple_pay';
    case GOOGLE_PAY = 'google_pay';
    case ACH = 'ach';
    case CASH = 'cash';
    case PAYPAL = 'paypal';
    case FPX = 'fpx';
    case DUITNOW_QR = 'duitnow_qr';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
