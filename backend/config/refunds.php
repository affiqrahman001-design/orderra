<?php

declare(strict_types=1);

return [
    'categories' => [
        'full_refund',
        'partial_refund',
        'missing_item',
        'wrong_item',
        'late_delivery',
        'store_credit',
    ],

    'statuses' => [
        'requested',
        'under_review',
        'approved',
        'rejected',
        'processed',
    ],

    'resolution_types' => [
        'full_refund',
        'partial_refund',
        'store_credit',
    ],

    'stages' => [
        'before_merchant_confirm' => [
            'statuses' => ['pending_payment', 'payment_authorized', 'placed'],
            'review_mode' => 'auto',
            'allowed_categories' => ['full_refund', 'store_credit'],
            'disallowed_categories' => ['partial_refund', 'missing_item', 'wrong_item', 'late_delivery'],
            'order_transition' => [
                'request' => 'cancelled',
                'processed_full' => 'cancelled',
                'processed_partial' => 'cancelled',
                'store_credit' => 'cancelled',
            ],
        ],

        'after_confirm_before_kitchen' => [
            'statuses' => ['confirmed'],
            'review_mode' => 'review',
            'allowed_categories' => ['full_refund', 'partial_refund', 'store_credit'],
            'disallowed_categories' => ['missing_item', 'wrong_item', 'late_delivery'],
            'order_transition' => [
                'request' => 'refund_pending',
                'processed_full' => 'refunded',
                'processed_partial' => 'partially_refunded',
                'store_credit' => 'partially_refunded',
            ],
        ],

        'after_kitchen_start' => [
            'statuses' => [
                'preparing',
                'ready',
                'served',
                'bill_requested',
                'ready_for_pickup',
                'awaiting_rider',
                'rider_assigned',
            ],
            'review_mode' => 'review',
            'allowed_categories' => ['partial_refund', 'missing_item', 'wrong_item', 'store_credit'],
            'disallowed_categories' => ['full_refund', 'late_delivery'],
            'order_transition' => [
                'request' => 'refund_pending',
                'processed_full' => 'refunded',
                'processed_partial' => 'partially_refunded',
                'store_credit' => 'partially_refunded',
            ],
        ],

        'after_customer_handover' => [
            'statuses' => [
                'picked_up',
                'near_customer',
                'delivered',
                'picked_up_by_customer',
                'paid_at_table',
                'completed',
            ],
            'review_mode' => 'review_strict',
            'allowed_categories' => ['partial_refund', 'missing_item', 'wrong_item', 'late_delivery', 'store_credit'],
            'disallowed_categories' => ['full_refund'],
            'order_transition' => [
                'request' => 'refund_pending',
                'processed_full' => 'refunded',
                'processed_partial' => 'partially_refunded',
                'store_credit' => 'partially_refunded',
            ],
        ],
    ],

    'amount_rules' => [
        'full_refund' => [
            'strategy' => 'order_total',
        ],
        'partial_refund' => [
            'strategy' => 'percentage_of_total',
            'max_bps' => 5000,
        ],
        'missing_item' => [
            'strategy' => 'percentage_of_total',
            'max_bps' => 3500,
        ],
        'wrong_item' => [
            'strategy' => 'percentage_of_total',
            'max_bps' => 4000,
        ],
        'late_delivery' => [
            'strategy' => 'fees_only',
        ],
        'store_credit' => [
            'strategy' => 'percentage_of_total',
            'max_bps' => 2500,
        ],
    ],

    'payment_placeholder' => [
        'create_refund_hook_on_processed' => true,
        'notes' => 'Demo-safe refund placeholder only. No live provider refund executed.',
    ],
];
