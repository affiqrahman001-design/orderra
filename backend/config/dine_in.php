<?php

declare(strict_types=1);

return [
    'tables' => [
        'default_status' => 'active',
    ],

    'qr_sessions' => [
        'code_prefix' => 'QR',
        'code_length' => 6,
        'default_status' => 'open',
        'reuse_open_session' => true,
        /** Demo portfolios: optional soft TTL hint for QR sessions (hours). Null = no scheduled expiry while active. */
        'demo_session_ttl_hours' => null,
        'active_statuses' => ['open', 'bill_requested', 'payment_ready'],
        'allow_attach_cart_statuses' => ['open'],
        'allow_waiter_call_statuses' => ['open', 'bill_requested'],
        'allow_request_bill_statuses' => ['open', 'bill_requested'],
        'default_party_size' => 1,
        'frontend_base_url' => env('FRONTEND_URL', 'http://localhost:5173'),
        'frontend_join_path' => '/dine-in/join',
        /** Optional shorter landing path mounted by the SPA (see RootLayout). */
        'frontend_public_qr_path' => '/qr',
        'require_linked_orders_for_bill_request' => true,
    ],

    'waiter_calls' => [
        'cooldown_seconds' => 60,
    ],

    'add_more_items' => [
        'mutate_existing_order' => false,
        'strategy' => 'new_cart_new_order_same_session',
    ],

    'split_bill' => [
        'enabled' => true,
        'supported_types' => ['equal', 'by_item'],
        'allowed_session_statuses' => ['open', 'bill_requested'],
        'max_participants' => 12,
        'single_active_plan_per_session' => true,
        'allow_replace_draft' => true,
        'require_full_item_allocation' => true,
        'finalizable_statuses' => ['draft'],
        'require_orders_before_create' => true,
        'require_no_open_attached_carts' => true,
        'require_participant_totals_match_session_total' => true,
        'surface_summary_on_session' => true,
    ],
];
