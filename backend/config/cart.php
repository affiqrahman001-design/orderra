<?php

declare(strict_types=1);

return [
    'default_status' => 'cart_draft',

    'token_header' => 'X-Cart-Token',

    'expiry_minutes' => 240,

    'line_limits' => [
        'max_lines' => 30,
        'max_quantity_per_line' => 10,
    ],

    'defaults' => [
        'currency' => env('ORDERRA_CURRENCY', 'USD'),
        'fulfillment_type' => 'delivery',
        'source' => 'web',
        'tip_type' => 'none',
        'tip_value' => 0,
    ],
];
