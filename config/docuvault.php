<?php

return [
    'documents' => [
        'disk' => env('DOCUVAULT_DOCUMENT_DISK', 'local'),
        'directory' => env('DOCUVAULT_DOCUMENT_DIRECTORY', 'documents'),
        'default_type' => env('DOCUVAULT_DEFAULT_DOCUMENT_TYPE', 'general'),
        'max_upload_size' => env('DOCUVAULT_DOCUMENT_MAX_UPLOAD_SIZE', 51200),

        /*
         * Maps the AI-returned document_type to a canonical type
         * (App\Enums\DocumentType). The first canonical type whose keyword
         * appears (case-insensitively) in the AI value wins; order matters.
         */
        'type_map' => [
            'ssm' => ['ssm', 'suruhanjaya syarikat', 'company registration', 'ssm form', 'borang', 'section 14', 'section 17'],
            'bank_account' => ['bank', 'statement', 'account', 'penyata'],
        ],
    ],

    'company' => [
        /*
         * Extracted-field keys (matched against a normalized field_key) that
         * hold the company / account holder name, in priority order.
         */
        'name_keys' => [
            'company_name',
            'name_of_company',
            'registered_name',
            'company',
            'account_name',
            'account_holder',
            'account_holder_name',
            'name',
        ],
    ],

    'raraxuan' => [
        'document_agent' => env('RARAXUAN_DOCUMENT_AGENT', 'doc-universal-extractor'),
    ],
];
