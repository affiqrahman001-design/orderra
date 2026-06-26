<?php

declare(strict_types=1);

return [
    'placement' => [
        'initial_status' => 'placed',
        'order_code_prefix' => env('ORDERRA_ORDER_CODE_PREFIX', 'ORD'),
    ],

    'terminal_statuses' => [
        'completed',
        'cancelled',
        'refunded',
    ],

    'transitions' => [
        'common' => [
            'pending_payment' => ['payment_authorized', 'cancelled'],
            'payment_authorized' => ['placed', 'cancelled'],
            'placed' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled', 'refund_pending'],
            'preparing' => ['ready', 'cancelled', 'refund_pending'],
            'refund_pending' => ['refunded', 'partially_refunded'],
            'delivered' => ['completed', 'refund_pending', 'partially_refunded'],
            'picked_up_by_customer' => ['completed', 'refund_pending', 'partially_refunded'],
            'paid_at_table' => ['completed', 'refund_pending', 'partially_refunded'],
            'partially_refunded' => ['completed'],
        ],

        'delivery' => [
            'ready' => ['awaiting_rider'],
            'awaiting_rider' => ['rider_assigned', 'cancelled', 'refund_pending'],
            'rider_assigned' => ['picked_up', 'refund_pending'],
            'picked_up' => ['near_customer', 'refund_pending', 'partially_refunded'],
            'near_customer' => ['delivered', 'refund_pending', 'partially_refunded'],
        ],

        'pickup' => [
            'ready' => ['ready_for_pickup'],
            'ready_for_pickup' => ['picked_up_by_customer', 'cancelled'],
        ],

        'dine_in' => [
            'ready' => ['served'],
            'served' => ['bill_requested', 'refund_pending', 'partially_refunded'],
            'bill_requested' => ['paid_at_table', 'refund_pending', 'partially_refunded'],
        ],
    ],
];
