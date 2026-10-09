<?php

/**
 * Rate Limiting Configuration
 * 
 * This file documents the rate limiting rules applied to API endpoints.
 * Laravel uses the 'throttle:maxAttempts,decayMinutes' middleware format.
 * 
 * Rate limiting helps protect against:
 * - Brute force attacks
 * - DDoS attacks
 * - API abuse
 * - Resource exhaustion
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Endpoints
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'User login endpoint - prevents brute force attacks',
        ],
        'register' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'User registration endpoints - prevents spam registrations',
        ],
        'refresh' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'Token refresh endpoint',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Endpoints
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'resend' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'OTP resend - prevents spam and cost abuse',
        ],
        'verify' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'OTP verification - prevents brute force',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Endpoints
    |--------------------------------------------------------------------------
    */
    'password' => [
        'forgot' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'Password reset request - prevents email spam',
        ],
        'verify_otp' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'Password reset OTP verification',
        ],
        'reset' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'Password reset - prevents brute force',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Store & Invitation Endpoints
    |--------------------------------------------------------------------------
    */
    'store' => [
        'create' => [
            'limit' => 3,
            'decay' => 1, // 3 stores per minute per user
            'description' => 'Store creation - prevents abuse',
        ],
        'invitation_send' => [
            'limit' => 3,
            'decay' => 1, // 3 invitations per minute
            'description' => 'Send invitation - prevents spam',
        ],
        'invitation_accept' => [
            'limit' => 3,
            'decay' => 1, // 3 accepts per minute per IP
            'description' => 'Accept invitation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Endpoints
    |--------------------------------------------------------------------------
    */
    'public' => [
        'contact_message' => [
            'limit' => 3,
            'decay' => 1, // 3 messages per minute per IP
            'description' => 'Contact form - prevents spam',
        ],
        'search' => [
            'limit' => 3,
            'decay' => 1, // 3 searches per minute per IP
            'description' => 'Search endpoints - prevents API abuse',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Endpoints
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'login' => [
            'limit' => 3,
            'decay' => 1, // 3 attempts per minute
            'description' => 'Admin login - strict security',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Storage
    |--------------------------------------------------------------------------
    | 
    | Laravel uses the cache driver for rate limiting by default.
    | For better performance and distributed systems, consider using Redis:
    | 
    | CACHE_DRIVER=redis
    | REDIS_HOST=127.0.0.1
    | REDIS_PASSWORD=null
    | REDIS_PORT=6379
    |
    */
    'storage' => env('RATE_LIMIT_STORAGE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    | Custom Rate Limiting Keys
    |--------------------------------------------------------------------------
    |
    | You can customize rate limiting by IP, user, or email:
    | - throttle:60,1 (by IP address - default)
    | - throttle:60,1,email (by email address)
    | - throttle:60,1,userId (by authenticated user ID)
    |
    */
    'key_strategy' => [
        'default' => 'ip', // Rate limit by IP address
        'authenticated' => 'user', // Rate limit by user ID when authenticated
    ],
];
