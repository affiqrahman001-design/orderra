<?php

declare(strict_types=1);

return [
    'guard' => [
        'enabled' => (bool) env('ADMIN_REFERENCE_GUARD_ENABLED', true),
        'header_name' => env('ADMIN_REFERENCE_HEADER', 'X-ORDERra-Admin-Key'),
        'token' => env('ADMIN_REFERENCE_TOKEN'),
        'readonly_mode' => (bool) env('ADMIN_REFERENCE_READONLY', false),
    ],

    'rate_limit' => [
        'per_minute' => (int) env('ADMIN_REFERENCE_RATE_LIMIT', 60),
    ],
];
