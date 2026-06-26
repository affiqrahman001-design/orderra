<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 50,
    ],

    'audit' => [
        'channels' => [
            'admin',
            'simulation',
        ],

        'statuses' => [
            'completed',
            'failed',
            'blocked',
        ],

        'actions' => [
            'order.transition',
            'refund.review',
            'support_ticket.transition',
        ],
    ],

    'notifications' => [
        'channels' => [
            'in_app',
            'email_placeholder',
            'sms_placeholder',
            'ops_webhook',
        ],

        'statuses' => [
            'simulated',
            'failed',
        ],

        'types' => [
            'order_status_changed',
            'refund_status_changed',
            'support_ticket_status_changed',
        ],

        'body_preview_limit' => 180,
    ],
];
