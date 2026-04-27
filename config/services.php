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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'ttlock' => [
        'client_id' => env('TTLOCK_CLIENT_ID'),
        'client_secret' => env('TTLOCK_CLIENT_SECRET'),
        'username' => env('TTLOCK_USERNAME'),
        'password' => env('TTLOCK_PASSWORD'),
        'base_url' => env('TTLOCK_BASE_URL', 'https://euapi.ttlock.com'),
        'ca_bundle' => env('TTLOCK_CA_BUNDLE'),
    ],

    'whatsapp' => [
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
    ],

];
