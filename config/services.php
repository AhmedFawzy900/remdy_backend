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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // Google Play In-App Purchase Configuration
        'play_console_credentials' => env('GOOGLE_PLAY_CREDENTIALS_PATH', storage_path('app/remdy-9668a-4bba7c728033.json')),
        'webhook_secret' => env('GOOGLE_WEBHOOK_SECRET'),
        'webhook_url' => env('GOOGLE_WEBHOOK_URL', env('APP_URL') . '/api/webhooks/google'),
        'package_name' => env('GOOGLE_PACKAGE_NAME'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
        'key_id' => env('APPLE_KEY_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH', storage_path('app/private/AuthKey_' . env('APPLE_KEY_ID', '') . '.p8')),
        // In-App Purchase Webhook Configuration
        'shared_secret' => env('APPLE_SHARED_SECRET'),
        'webhook_url' => env('APPLE_WEBHOOK_URL', env('APP_URL') . '/api/webhooks/apple'),
        'sandbox_mode' => env('APPLE_SANDBOX_MODE', true),
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'test_key' => env('STRIPE_TEST_KEY'),
        'test_secret' => env('STRIPE_TEST_SECRET'),
    ],
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'.storage_path('app/remdy-9668a-4bba7c728033.json')),
    ],

];
