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

    'ebilling' => [
        'username' => env('EBILLING_USERNAME'),
        'shared_key' => env('EBILLING_SHARED_KEY'),
        'server_url' => env('EBILLING_SERVER_URL'),
        'post_url' => env('EBILLING_POST_URL'),
        // Webhook hardening: comma-separated IP whitelist and optional shared
        // secret expected in header X-Webhook-Secret (or query "token").
        // When both are empty, the webhook stays open but every call is
        // logged with a warning so misuse is auditable.
        'webhook_allowed_ips' => env('EBILLING_WEBHOOK_ALLOWED_IPS', ''),
        'webhook_secret' => env('EBILLING_WEBHOOK_SECRET'),
    ],

];
