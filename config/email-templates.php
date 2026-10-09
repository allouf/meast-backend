<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Template Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for email templates, including
    | default settings, styling options, and template mappings.
    |
    */

    'defaults' => [
        'from_name' => env('MAIL_FROM_NAME', config('app.name')),
        'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'reply_to' => env('MAIL_REPLY_TO', 'support@example.com'),
        'company_name' => config('app.name'),
        'company_url' => config('app.url'),
        'support_email' => 'support@example.com',
        'support_phone' => '+1 (555) 123-4567',
    ],

    'templates' => [
        'verify-email' => [
            'subject' => 'Verify Your Email Address',
            'template' => 'emails.verify-email',
            'expires_in' => 10, // minutes
        ],
        'reset-password' => [
            'subject' => 'Reset Your Password',
            'template' => 'emails.reset-password',
            'expires_in' => 15, // minutes
        ],
        'welcome' => [
            'subject' => 'Welcome to :app_name!',
            'template' => 'emails.welcome',
        ],
        'store_invitation' => [
            'subject' => 'You\'re invited to join :store_name',
            'template' => 'emails.store_invitation',
            'expires_in' => 168, // hours (7 days)
        ],
        'account-upgrade' => [
            'subject' => 'Confirm Your Account Upgrade',
            'template' => 'emails.confirm-upgrade',
            'expires_in' => 24, // hours
        ],
        'order-confirmation' => [
            'subject' => 'Order Confirmation #:order_number',
            'template' => 'emails.order-confirmation',
        ],
        'payment-failed' => [
            'subject' => 'Payment Failed - Order #:order_number',
            'template' => 'emails.payment-failed',
        ],
        'product-review-request' => [
            'subject' => 'How was your purchase of :product_name?',
            'template' => 'emails.product-review-request',
        ],
        'account-suspended' => [
            'subject' => 'Account Suspension Notice',
            'template' => 'emails.account-suspended',
        ],
    ],

    'styling' => [
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'success_color' => '#48bb78',
        'warning_color' => '#ed8936',
        'error_color' => '#f56565',
        'info_color' => '#4299e1',
        'font_family' => 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
        'border_radius' => '8px',
        'box_shadow' => '0 4px 12px rgba(0, 0, 0, 0.1)',
    ],

    'features' => [
        'tracking' => [
            'enabled' => env('EMAIL_TRACKING_ENABLED', true),
            'track_opens' => true,
            'track_clicks' => true,
        ],
        'unsubscribe' => [
            'enabled' => env('EMAIL_UNSUBSCRIBE_ENABLED', true),
            'mandatory' => false, // Set to true for marketing emails
        ],
        'preview' => [
            'enabled' => env('EMAIL_PREVIEW_ENABLED', true),
            'url' => '/email-preview',
        ],
    ],

    'queue' => [
        'enabled' => env('EMAIL_QUEUE_ENABLED', true),
        'queue_name' => env('EMAIL_QUEUE_NAME', 'emails'),
        'delay' => 0, // seconds
    ],

    'rate_limiting' => [
        'enabled' => env('EMAIL_RATE_LIMITING_ENABLED', true),
        'max_emails_per_minute' => 60,
        'max_emails_per_hour' => 1000,
        'max_emails_per_day' => 10000,
    ],

    'testing' => [
        'enabled' => env('EMAIL_TESTING_ENABLED', false),
        'test_email' => env('EMAIL_TEST_ADDRESS', 'test@example.com'),
        'log_emails' => env('EMAIL_LOG_ENABLED', true),
    ],
];
