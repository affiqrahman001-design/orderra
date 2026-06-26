<?php

declare(strict_types=1);

return [
    'categories' => [
        'refund_request',
        'missing_item',
        'wrong_item',
        'late_delivery',
        'delivery_issue',
        'payment_issue',
        'dine_in_issue',
    ],

    'statuses' => [
        'open',
        'under_review',
        'awaiting_resolution',
        'resolved',
        'closed',
    ],

    'default_status' => 'open',

    'transitions' => [
        'open' => ['under_review', 'closed'],
        'under_review' => ['awaiting_resolution', 'resolved', 'closed'],
        'awaiting_resolution' => ['resolved', 'closed'],
        'resolved' => ['closed'],
        'closed' => [],
    ],
];
