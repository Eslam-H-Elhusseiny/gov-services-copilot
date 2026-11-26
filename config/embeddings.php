<?php

return [

    'provider' => env('EMBEDDING_PROVIDER', 'gemini'),

    'model' => env('EMBEDDING_MODEL', 'gemini-embedding-001'),

    'key' => env('EMBEDDING_API_KEY'),

    'base_uri' => env('EMBEDDING_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),

    'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 768),

    'task_type' => env('EMBEDDING_TASK_TYPE', 'RETRIEVAL_DOCUMENT'),

    'timeout' => (int) env('EMBEDDING_TIMEOUT', 30),

];
