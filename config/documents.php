<?php

return [

    'disk' => env('DOCUMENT_DISK', 'local'),

    'max_upload_kb' => (int) env('DOCUMENT_MAX_UPLOAD_KB', 20480),

    'allowed_extensions' => array_values(array_filter(array_map(
        'trim',
        explode(',', strtolower((string) env('DOCUMENT_ALLOWED_EXTENSIONS', 'pdf,docx'))),
    ))),

    'chunk_size' => (int) env('DOCUMENT_CHUNK_SIZE', 1000),

    'chunk_overlap' => (int) env('DOCUMENT_CHUNK_OVERLAP', 200),

];
