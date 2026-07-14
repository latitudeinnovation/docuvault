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
            // Bahasa Melayu SSM documents.
            'nama_syarikat',
            'nama_perniagaan',
            'nama_pertubuhan',
            'account_name',
            'account_holder',
            'account_holder_name',
            'name',
            'nama',
        ],

        /*
         * Extracted-field keys that hold the company registration number
         * (e.g. from an SSM profile), in priority order.
         */
        'registration_keys' => [
            'registration_no',
            'registration_number',
            'company_no',
            'company_number',
            'company_registration_no',
            'ssm_no',
            'ssm_number',
            'registration',
            // Bahasa Melayu SSM documents. ("No. Pendaftaran" normalises to
            // "pendaftaran" because the classifier treats "." as a path separator.)
            'pendaftaran',
            'no_pendaftaran',
            'nombor_pendaftaran',
            'no_syarikat',
            'nombor_syarikat',
        ],
    ],

    'directors' => [
        /*
         * Keywords used to find the officers table within the extracted
         * `tables` array (matched against the table_name, case-insensitive).
         */
        'table_keys' => ['director', 'officer', 'board'],

        /*
         * Only officer rows whose designation contains one of these keywords are
         * stored as directors (case-insensitive). Secretaries/others are skipped.
         */
        'designations' => ['director'],

        /*
         * Substring matchers (case-insensitive) used to map each table row's
         * columns onto a person, in priority order per attribute.
         */
        'columns' => [
            'name' => ['name', 'address'],
            'ic' => ['ic', 'passport', 'nric', 'identity', 'identification'],
            'designation' => ['designation', 'position', 'role', 'title'],
            'appointed' => ['appointment', 'appointed', 'date'],
            'address' => ['address'],
        ],
    ],

    'shareholders' => [
        'table_keys' => ['shareholder', 'member'],

        'columns' => [
            'name' => ['name', 'company'],
            'ic' => ['ic', 'passport', 'registration', 'nric'],
            'shares' => ['share', 'shares', 'total'],
        ],
    ],

    'bank_accounts' => [
        /*
         * Extracted-field keys (matched against a normalized field_key, same
         * normalization as company.name_keys) that hold each bank-account
         * attribute, in priority order.
         */
        'account_no_keys' => ['account_no', 'account_number', 'acc_no'],

        'account_holder_keys' => ['account_holder_name', 'account_holder', 'account_name', 'company_name'],

        'bank_name_keys' => ['bank_account_product', 'account_product', 'bank_name', 'bank'],

        'account_type_keys' => ['account_type'],
    ],

    'raraxuan' => [
        'document_agent' => env('RARAXUAN_DOCUMENT_AGENT', 'doc-universal-extractor'),
    ],
];
