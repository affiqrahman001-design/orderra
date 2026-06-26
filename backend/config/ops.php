<?php

declare(strict_types=1);

return [
    'webhooks' => [
        'allowed_events' => [
            'payment.updated',
            'refund.updated',
            'rider.assigned',
            'rider.location_updated',
            'order.delivered',
        ],

        'aggregate_types' => [
            'payment',
            'refund',
            'delivery',
            'order',
        ],

        'event_aggregate_map' => [
            'payment.updated' => 'payment',
            'refund.updated' => 'refund',
            'rider.assigned' => 'delivery',
            'rider.location_updated' => 'delivery',
            'order.delivered' => 'order',
        ],

        'statuses' => [
            'processed',
            'replayed',
            'failed',
        ],

        'default_status' => 'processed',

        'replay' => [
            'enabled' => true,
            'max_attempts' => 5,
        ],
    ],
];
