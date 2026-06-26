<?php

namespace App\Enums;

enum PaymentProviderCode: string
{
    case DEMO_CARD = 'demo_card';
    case DEMO_WALLET = 'demo_wallet';
    case DEMO_BANK = 'demo_bank';
    case DEMO_CASH = 'demo_cash';
    case DEMO_PAYPAL = 'demo_paypal';
    case DEMO_MALAYSIA = 'demo_malaysia';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
