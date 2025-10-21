<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenRouter API integration used for the chatbot.
    |
    */

    'api_key' => env('OPENROUTER_API_KEY'),
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3.1:free'),
    
    /*
    |--------------------------------------------------------------------------
    | Request Configuration
    |--------------------------------------------------------------------------
    */
    
    'timeout' => env('OPENROUTER_TIMEOUT', 60),
    'max_tokens' => env('OPENROUTER_MAX_TOKENS', 2000),
    'temperature' => env('OPENROUTER_TEMPERATURE', 0.7),
    
    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    */
    
    'system_prompt' => env('OPENROUTER_SYSTEM_PROMPT', 'You are a helpful property rental assistant. You have access to information about clients, properties, and rental records. Provide accurate and helpful responses based only on the data provided. If you don\'t have information to answer a question, politely say so.'),
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    
    'rate_limit' => [
        'max_requests' => env('OPENROUTER_RATE_LIMIT_MAX', 60),
        'per_minutes' => env('OPENROUTER_RATE_LIMIT_PER', 1),
    ],
];
