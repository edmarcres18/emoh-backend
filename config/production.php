<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains production-specific configurations for the API
    |
    */

    'api' => [
        'version' => '1.0.0',
        'rate_limits' => [
            'default' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
            ],
            'auth' => [
                'login' => [
                    'max_attempts' => 10,
                    'decay_minutes' => 1,
                ],
                'register' => [
                    'max_attempts' => 5,
                    'decay_minutes' => 1,
                ],
                'otp' => [
                    'max_attempts' => 5,
                    'decay_minutes' => 1,
                ],
            ],
            'properties' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
            ],
        ],
        'security' => [
            'max_request_size' => 10240, // 10MB in KB
            'max_upload_size' => 5120,  // 5MB in KB
            'session_timeout' => 120,    // 2 hours in minutes
        ],
        'monitoring' => [
            'log_slow_queries' => true,
            'slow_query_threshold' => 1000, // milliseconds
            'log_failed_requests' => true,
            'log_suspicious_activity' => true,
        ],
    ],

    'logging' => [
        'channels' => [
            'api' => [
                'driver' => 'daily',
                'path' => storage_path('logs/api.log'),
                'level' => 'info',
                'days' => 30,
            ],
            'security' => [
                'driver' => 'daily',
                'path' => storage_path('logs/security.log'),
                'level' => 'warning',
                'days' => 90,
            ],
            'performance' => [
                'driver' => 'daily',
                'path' => storage_path('logs/performance.log'),
                'level' => 'info',
                'days' => 7,
            ],
        ],
    ],
];
