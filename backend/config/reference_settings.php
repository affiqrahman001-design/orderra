<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 50,
    ],

    'branch' => [
        'statuses' => [
            'active',
            'inactive',
        ],
    ],

    'delivery_zones' => [
        'statuses' => [
            'active',
            'inactive',
        ],
        'pricing_strategies' => [
            'flat',
            'distance',
            'hybrid',
        ],
    ],

    'tax_rules' => [
        'statuses' => [
            'active',
            'inactive',
        ],
        'fulfillment_types' => [
            'delivery',
            'pickup',
            'dine_in',
        ],
        'scopes' => [
            'order_subtotal',
            'delivery_fee',
            'service_fee',
        ],
        'charge_types' => [
            'fixed',
            'percentage',
        ],
    ],

    'fee_rules' => [
        'statuses' => [
            'active',
            'inactive',
        ],
        'fee_types' => [
            'service_fee',
            'small_order_fee',
            'packaging_fee',
            'peak_surcharge',
        ],
        'charge_types' => [
            'fixed',
            'percentage',
        ],
        'fee_kinds' => [
            'service_fee',
            'small_order_fee',
            'packaging_fee',
            'peak_surcharge',
        ],
        'calculation_types' => [
            'fixed',
            'percentage',
        ],
    ],

    'catalog' => [
        'product_flows' => [
            'none',
            'simple',
            'full',
        ],
        'selection_modes' => [
            'single',
            'multiple',
        ],
    ],

    'promos' => [
        'discount_types' => [
            'percentage',
            'fixed',
        ],
    ],
];
