<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RFQ / Custom Requests
    |--------------------------------------------------------------------------
    */

    'rfq_discovery_duration_days' => env('RFQ_DISCOVERY_DURATION_DAYS', 3),

    'rfq_open_duration_days' => env('RFQ_OPEN_DURATION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Revision System
    |--------------------------------------------------------------------------
    */

    'revision_request_window_days' => env('REVISION_REQUEST_WINDOW_DAYS', 7),

    'max_open_revision_requests' => env('MAX_OPEN_REVISION_REQUESTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Boost Campaigns
    |--------------------------------------------------------------------------
    */

    'boost_refund_policy' => env('BOOST_REFUND_POLICY', 'wallet_credit'),

    /*
    |--------------------------------------------------------------------------
    | Milestones
    |--------------------------------------------------------------------------
    */

    'max_milestones_per_order' => env('MAX_MILESTONES_PER_ORDER', 20),

];