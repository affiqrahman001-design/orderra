<?php

declare(strict_types=1);

namespace App\Enums;

enum FulfillmentType: string
{
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';
    case DINE_IN = 'dine_in';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
