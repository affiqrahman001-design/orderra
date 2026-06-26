<?php

declare(strict_types=1);

return [
    'currency' => env('ORDERRA_CURRENCY', 'USD'),

    'promo' => [
        'enabled' => true,
        'placeholder_code' => 'DEMO10',
        'placeholder_discount_type' => 'percentage',
        'placeholder_value' => 1000, // 10.00% in bps
        'discount_reduces_taxable_subtotal' => true,
    ],

    'tip' => [
        'enabled' => true,
        'allowed_fulfillment' => ['delivery', 'pickup', 'dine_in'],
        'default_type' => 'none',
        'preset_bps' => [1200, 1500, 1800],
        'max_amount_cents' => 5000,
    ],

    'rounding' => [
        'increment_cents' => 1,
    ],

    'fulfillment' => [
        'delivery' => [
            'service_fee' => [
                'type' => 'bps',
                'value' => 500,
                'min_cents' => 99,
                'max_cents' => 399,
                'taxable' => true,
            ],
            'delivery_fee' => [
                'type' => 'fixed',
                'value_cents' => 399,
                'taxable' => false,
            ],
            'small_order_fee' => [
                'enabled' => true,
                'threshold_cents' => 2000,
                'fee_cents' => 250,
                'taxable' => false,
            ],
        ],

        'pickup' => [
            'service_fee' => [
                'type' => 'bps',
                'value' => 0,
                'min_cents' => 0,
                'max_cents' => 0,
                'taxable' => false,
            ],
            'delivery_fee' => [
                'type' => 'fixed',
                'value_cents' => 0,
                'taxable' => false,
            ],
            'small_order_fee' => [
                'enabled' => false,
                'threshold_cents' => 0,
                'fee_cents' => 0,
                'taxable' => false,
            ],
        ],

        'dine_in' => [
            'service_fee' => [
                'type' => 'bps',
                'value' => 1000,
                'min_cents' => 0,
                'max_cents' => 0,
                'taxable' => true,
            ],
            'delivery_fee' => [
                'type' => 'fixed',
                'value_cents' => 0,
                'taxable' => false,
            ],
            'small_order_fee' => [
                'enabled' => false,
                'threshold_cents' => 0,
                'fee_cents' => 0,
                'taxable' => false,
            ],
        ],
    ],

    'fallback_tax' => [
        'country_code' => 'US',
        'state_code' => 'NY',
        'city_code' => null,
        'rules' => [
            [
                'name' => 'sales_tax',
                'rate_bps' => 887,
                'applies_to' => ['subtotal', 'service_fee'],
            ],
        ],
    ],

    'refund_impacts' => [
        'tax_refundable' => true,
        'service_fee_refundable' => false,
        'delivery_fee_refundable' => false,
        'tip_refundable' => true,
    ],
];
