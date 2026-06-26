<?php

declare(strict_types=1);

return [
    'types' => [
        'self',
        'third_party_placeholder',
    ],

    'statuses' => [
        'active',
        'offline',
    ],

    'simulation' => [
        'flow' => [
            'awaiting_rider',
            'rider_assigned',
            'picked_up',
            'near_customer',
            'delivered',
        ],

        'eta_minutes' => [
            'awaiting_rider' => 18,
            'rider_assigned' => 14,
            'picked_up' => 9,
            'near_customer' => 3,
            'delivered' => 0,
        ],

        'default_rider_pool' => [
            [
                'rider_code' => 'RDR-001',
                'name' => 'Jordan P.',
                'type' => 'self',
                'vehicle_type' => 'bike',
            ],
            [
                'rider_code' => 'RDR-002',
                'name' => 'Mia R.',
                'type' => 'self',
                'vehicle_type' => 'scooter',
            ],
            [
                'rider_code' => 'RDR-TP-01',
                'name' => 'Third-Party Placeholder',
                'type' => 'third_party_placeholder',
                'vehicle_type' => 'unknown',
            ],
        ],

        'coordinates' => [
            'awaiting_rider' => ['lat' => null, 'lng' => null],
            'rider_assigned' => ['lat' => 40.730610, 'lng' => -73.935242],
            'picked_up' => ['lat' => 40.732000, 'lng' => -73.931000],
            'near_customer' => ['lat' => 40.734200, 'lng' => -73.928100],
            'delivered' => ['lat' => 40.735500, 'lng' => -73.926400],
        ],

        'activation_status' => 'awaiting_rider',

        'assignable_order_statuses' => [
            'ready',
            'awaiting_rider',
            'rider_assigned',
            'picked_up',
            'near_customer',
        ],
    ],
];
