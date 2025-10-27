<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        // Local development - HTTP
        'http://localhost',
        'http://localhost:8000',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        // Local development - HTTPS
        'https://localhost',
        'https://localhost:8000',
        'https://127.0.0.1',
        'https://127.0.0.1:8000',
        // Production domains
        'https://chuweydev.site',
        'https://emoh.chuweydev.site',
        // Additional from environment
        env('CORS_ALLOWED_ORIGINS') ? env('CORS_ALLOWED_ORIGINS') : null,
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
