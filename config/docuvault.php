<?php

return [
    'documents' => [
        'disk' => env('DOCUVAULT_DOCUMENT_DISK', 'local'),
        'directory' => env('DOCUVAULT_DOCUMENT_DIRECTORY', 'documents'),
        'default_type' => env('DOCUVAULT_DEFAULT_DOCUMENT_TYPE', 'general'),
        'max_upload_size' => env('DOCUVAULT_DOCUMENT_MAX_UPLOAD_SIZE', 51200),
    ],

    'raraxuan' => [
        'document_agent' => env('RARAXUAN_DOCUMENT_AGENT', 'doc-universal-extractor'),
    ],
];
