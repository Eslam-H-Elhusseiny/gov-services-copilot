<?php

return [

    'url' => env('QDRANT_URL', 'http://qdrant:6333'),

    'api_key' => env('QDRANT_API_KEY'),

    'collection' => env('QDRANT_COLLECTION', 'government_chunks'),

    'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 768),

    'distance' => env('VECTOR_DISTANCE', 'Cosine'),

    'timeout' => (int) env('VECTOR_TIMEOUT', 30),

];
