<?php

return [

    /*
  |--------------------------------------------------------------------------
  | Demo / Live Guard
  |--------------------------------------------------------------------------
  */

    'demo_mode' => env('PAYMENTS_DEMO_MODE', true),
    'block_live_execution' => env('PAYMENTS_BLOCK_LIVE_EXECUTION', true),
    'allow_webhook_simulation' => env('PAYMENTS_ALLOW_WEBHOOK_SIMULATION', true),
    'safe_provider_modes' => array_filter(array_map(
        'trim',
        explode(',', env('PAYMENTS_SAFE_PROVIDER_MODES', 'demo,sandbox,test'))
    )),

    'demo_provider_drivers' => array_filter(array_map(
        'trim',
        explode(',', env('PAYMENTS_DEMO_PROVIDER_DRIVERS', 'demo'))
    )),

    /*
  |--------------------------------------------------------------------------
  | Defaults
  |--------------------------------------------------------------------------
  */

    'default_country' => env('PAYMENTS_DEFAULT_COUNTRY', 'US'),
    'default_currency' => env('PAYMENTS_DEFAULT_CURRENCY', 'USD'),

    /*
  |--------------------------------------------------------------------------
  | Payment Method Registry
  |--------------------------------------------------------------------------
  */

    'methods' => [

        'card' => [
            'label' => 'Card',
            'family' => 'card',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['US'],
            'provider_codes' => ['demo_card'],
        ],

        'apple_pay' => [
            'label' => 'Apple Pay',
            'family' => 'wallet',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['US'],
            'provider_codes' => ['demo_wallet'],
        ],

        'google_pay' => [
            'label' => 'Google Pay',
            'family' => 'wallet',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['US'],
            'provider_codes' => ['demo_wallet'],
        ],

        'ach' => [
            'label' => 'ACH',
            'family' => 'bank',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['US'],
            'provider_codes' => ['demo_bank'],
        ],

        'cash' => [
            'label' => 'Cash',
            'family' => 'offline',
            'kind' => 'offline',
            'demo_enabled' => true,
            'requires_intent' => false,
            'supports_manual_simulation' => false,
            'countries' => ['US', 'MY'],
            'provider_codes' => ['demo_cash'],
        ],

        'paypal' => [
            'label' => 'PayPal',
            'family' => 'wallet',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['US'],
            'provider_codes' => ['demo_paypal'],
        ],

        /*
    |--------------------------------------------------------------------------
    | Malaysia standby reference only
    |--------------------------------------------------------------------------
    */

        'fpx' => [
            'label' => 'FPX',
            'family' => 'bank',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['MY'],
            'provider_codes' => ['demo_malaysia'],
        ],

        'duitnow_qr' => [
            'label' => 'DuitNow QR',
            'family' => 'qr',
            'kind' => 'digital',
            'demo_enabled' => true,
            'requires_intent' => true,
            'supports_manual_simulation' => true,
            'countries' => ['MY'],
            'provider_codes' => ['demo_malaysia'],
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Payment Provider Registry
  |--------------------------------------------------------------------------
  */

    'providers' => [

        'demo_card' => [
            'label' => 'Demo Card Gateway',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['card'],
        ],

        'demo_wallet' => [
            'label' => 'Demo Wallet Gateway',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['apple_pay', 'google_pay'],
        ],

        'demo_bank' => [
            'label' => 'Demo Bank Gateway',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['ach'],
        ],

        'demo_cash' => [
            'label' => 'Demo Cash Reference',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['cash'],
        ],

        'demo_paypal' => [
            'label' => 'Demo PayPal Gateway',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['paypal'],
        ],

        'demo_malaysia' => [
            'label' => 'Demo Malaysia Gateway',
            'driver' => 'demo',
            'mode' => 'demo',
            'live_enabled' => false,
            'supported_methods' => ['fpx', 'duitnow_qr'],
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Simulation Outcomes
  |--------------------------------------------------------------------------
  */

    'simulation' => [
        'default_outcome' => 'success',
        'allowed_outcomes' => ['success', 'failed', 'pending'],
        'pending_auto_expires_after_minutes' => 30,
    ],

    /*
  |--------------------------------------------------------------------------
  | Capability Placeholder
  |--------------------------------------------------------------------------
  */

    'country_capabilities' => [
        'US' => [
            'methods' => ['card', 'apple_pay', 'google_pay', 'ach', 'cash', 'paypal'],
        ],
        'MY' => [
            'methods' => ['cash', 'fpx', 'duitnow_qr'],
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Branch Override Placeholder
  |--------------------------------------------------------------------------
  |
  | Keep config-driven for now.
  | DB override can come later without changing core payment contract.
  |
  */

    'branch_overrides' => [
        // 'main-branch' => [
        //     'enabled_methods' => ['card', 'cash', 'paypal'],
        // ],
    ],

    'webhook_simulation' => [
        'allowed_events' => [
            'payment_authorized',
            'payment_pending',
            'payment_failed',
        ],
    ],

    'refund_hook_simulation' => [
        'allowed_types' => [
            'refund_requested',
            'refund_review_pending',
            'partial_refund_requested',
            'store_credit_requested',
        ],
    ],

];
