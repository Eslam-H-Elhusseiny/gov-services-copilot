<?php

return [

    'default' => env('AI_DEFAULT_PROVIDER', 'groq'),

    'fallback' => env('AI_FALLBACK_PROVIDER', 'gemini'),

    'providers' => [

        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_COMPLETION_MODEL', 'openai/gpt-oss-120b'),
            'base_uri' => env('GROQ_BASE_URI', 'https://api.groq.com/openai/v1'),
            'timeout' => (int) env('GROQ_TIMEOUT', 30),
        ],

        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_COMPLETION_MODEL', 'gemini-2.5-flash'),
            'base_uri' => env('GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        ],

    ],

];
