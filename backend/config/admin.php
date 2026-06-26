<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 50,
    ],

    'dashboard' => [
        'recent_limit' => 5,

        'active_order_statuses' => [
            'payment_authorized',
            'placed',
            'confirmed',
            'preparing',
            'ready',
            'awaiting_rider',
            'rider_assigned',
            'picked_up',
            'near_customer',
            'ready_for_pickup',
            'served',
            'bill_requested',
            'paid_at_table',
            'refund_pending',
            'partially_refunded',
        ],

        'refund_attention_statuses' => [
            'requested',
            'under_review',
            'approved',
        ],

        'support_attention_statuses' => [
            'open',
            'under_review',
            'awaiting_resolution',
        ],

        'active_qr_session_statuses' => [
            'open',
            'bill_requested',
            'payment_ready',
        ],

        'active_delivery_assignment_statuses' => [
            'awaiting_rider',
            'rider_assigned',
            'picked_up',
            'near_customer',
        ],

        'pending_payment_intent_statuses' => [
            'draft',
            'pending',
            'processing',
        ],
    ],

    'demo_scenarios' => [
        [
            'key' => 'payment_webhook',
            'label' => 'Payment webhook simulation',
            'method' => 'POST',
            'path' => '/api/v1/simulation/payments/intents/{paymentIntentId}/webhooks',
            'notes' => 'Guna event payment_authorized, payment_pending, atau payment_failed.',
        ],
        [
            'key' => 'payment_refund_hook',
            'label' => 'Payment refund hook simulation',
            'method' => 'POST',
            'path' => '/api/v1/simulation/payments/intents/{paymentIntentId}/refund-hooks',
            'notes' => 'Guna refund_requested, refund_review_pending, partial_refund_requested, atau store_credit_requested.',
        ],
        [
            'key' => 'rider_assignment',
            'label' => 'Rider assignment simulation',
            'method' => 'POST',
            'path' => '/api/v1/admin/riders/orders/{orderId}/assignments',
            'notes' => 'Boleh assign rider demo kepada order delivery yang sesuai.',
        ],
        [
            'key' => 'rider_advance',
            'label' => 'Rider progress simulation',
            'method' => 'POST',
            'path' => '/api/v1/admin/riders/assignments/{deliveryAssignmentId}/advance',
            'notes' => 'Advance timeline daripada rider_assigned ke delivered.',
        ],
        [
            'key' => 'ops_webhook_store',
            'label' => 'Ops webhook simulation',
            'method' => 'POST',
            'path' => '/api/v1/simulation/ops/webhooks',
            'notes' => 'Generate ops event untuk payment, refund, delivery, atau order.',
        ],
        [
            'key' => 'ops_webhook_replay',
            'label' => 'Ops webhook replay',
            'method' => 'POST',
            'path' => '/api/v1/simulation/ops/webhooks/{opsWebhookEventId}/replay',
            'notes' => 'Replay event demo-safe sahaja.',
        ],
    ],
];
