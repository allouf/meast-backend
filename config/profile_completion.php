<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profile completion cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'key_prefix' => 'user_job_profile_completion',
        'ttl_seconds' => (int) env('PROFILE_COMPLETION_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum completion to access job-postings (apply)
    | Candidate cannot apply until profile completion >= this (0–100).
    | 100 = full profile required (work experiences, educations, skills, etc.).
    |--------------------------------------------------------------------------
    */
    'minimum_percentage_to_apply' => (int) env('PROFILE_COMPLETION_MIN_PERCENTAGE', 0),

    /*
    |--------------------------------------------------------------------------
    | Weighted sections (must sum to 100)
    | Each key = User model relation name (loadCount). Value = weight (0-100).
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'workExperiences' => 25,
        'educations' => 25,
        'skills' => 25,
        'languages' => 10,
        'certifications' => 5,
        'projects' => 5,
        'links' => 5,
    ],
];
