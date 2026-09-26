<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'google' => [

        'sheets' => [

            'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),

            'credentials_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH', storage_path('app/google/credentials.json')),

            'app_name' => env('APP_NAME', 'UMSS Cursos'),

            'export_cron' => env('SHEETS_EXPORT_CRON', '0 */3 * * *'),

            'export_debounce_seconds' => env('SHEETS_EXPORT_DEBOUNCE_SECONDS', 60),

            'oauth' => [
                'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
                'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
                'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/').'/admin/google-sheets/callback'),
            ],

        ],

    ],

];
