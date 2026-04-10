<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    // Look into adding ALLOWED_EMAIL_DOMAINS to here as it is part of 'magic' more than 'auth'
    'magic' => [
        'expires' => env('MAGIC_ACCESS_TOKEN_EXPIRES', 5),
    ],

    'sepa' => [
        'account_name' => env('DEFAULT_SEPA_ACCOUNT_NAME', ''),
        'iban' => env('DEFAULT_SEPA_IBAN', ''),
    ],

    'super' => [
        'seeder' => env('SUPER_ADMIN_EMPLOYEE_EMAIL') != '' ? env('SUPER_ADMIN_EMPLOYEE_EMAIL', 'jpieters@almere.nl') : 'jpieters@almere.nl'
    ],
];
