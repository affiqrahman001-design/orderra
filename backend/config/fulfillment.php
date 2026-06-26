<?php

declare(strict_types=1);

return [
    'types' => ['delivery', 'pickup', 'dine_in'],

    'delivery' => [
        'required_customer_fields' => ['name', 'phone'],
        'required_address_fields' => [
            'address_line1',
            'city',
            'state',
            'postal_code',
            'country_code',
        ],
    ],

    'pickup' => [
        'required_customer_fields' => ['name', 'phone'],
        'pickup_code_length' => 6,
    ],

    'dine_in' => [
        'required_context_fields' => ['table_label'],
        'default_party_size' => 1,
    ],
];
