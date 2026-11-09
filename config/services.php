<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', '100000000000000'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', 'uptracker-whatsapp-token'),
    ],

    'uptracker' => [
        'max_users' => (int) env('UPTRACKER_MAX_USERS', 5),
        'max_services_per_user' => (int) env('UPTRACKER_MAX_SERVICES', 30),
        'cpu_threshold_percent' => (float) env('UPTRACKER_CPU_THRESHOLD', 15.0),
    ],

];
