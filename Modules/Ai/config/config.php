<?php

return [
    'runpod' => [
        /*
         * RunPod proxy base (no trailing slash).
         * Example: https://gghpuchjjuytib-8000.proxy.runpod.net
         */
        'base_url' => rtrim((string) env('RUNPOD_BASE_URL', 'https://gghpuchjjuytib-8000.proxy.runpod.net'), '/'),

        /*
         * HTTP path for the upstream call (leading slash).
         * - OpenAI-compatible: /v1/chat/completions
         * - RunPod serverless-style proxy: /invocations
         */
        'chat_completions_path' => env('RUNPOD_CHAT_COMPLETIONS_PATH', '/v1/chat/completions'),

        /*
         * How to map our OpenAI-shaped payload to the path above:
         * - auto: use "invocations" style if path contains "invocations", else "openai"
         * - openai: POST body is { model, messages, temperature, ... } (no wrapper)
         * - invocations: wrap body (see invocations_wrapper)
         */
        'endpoint_style' => env('RUNPOD_ENDPOINT_STYLE', 'auto'),

        /*
         * When endpoint_style is invocations:
         * - input: POST { "input": { ... same fields as OpenAI chat body ... } } (common RunPod worker)
         * - none: POST the OpenAI body as root JSON (path-only change)
         */
        'invocations_wrapper' => env('RUNPOD_INVOCATIONS_WRAPPER', 'input'),

        'api_key' => env('RUNPOD_API_KEY', ''),

        'default_model' => env('RUNPOD_DEFAULT_MODEL', 'mistralai/Mistral-7B-Instruct-v0.3'),

        'default_temperature' => (float) env('RUNPOD_DEFAULT_TEMPERATURE', 0.7),

        'timeout' => (int) env('RUNPOD_TIMEOUT', 120),
    ],
];
