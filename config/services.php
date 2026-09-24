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

        // ⚠️ Toute valeur lue par le service DOIT être déclarée ici : avec la
        // config en cache (`artisan optimize`), `env()` renvoie null partout
        // ailleurs. Des identifiants lus par `env()` dans un service se
        // retrouvaient vides en production dès le premier `optimize`.
        'auth_mode' => env('EBILLING_AUTH_MODE', 'oauth'),
        'oauth_token_url' => env('EBILLING_OAUTH_TOKEN_URL', ''),
        'oauth_client_id' => env('EBILLING_OAUTH_CLIENT_ID', ''),
        'oauth_client_secret' => env('EBILLING_OAUTH_CLIENT_SECRET', ''),
        'oauth_scope' => env('EBILLING_OAUTH_SCOPE', ''),
    ],

    // Passerelles historiques, encore référencées par le contrôleur de paiement.
    'airtel' => ['url' => env('AIRTEL_API_URL', 'https://api.airtel.com/payment')],
    'moov' => ['url' => env('MOOV_API_URL', 'https://api.moov.com/payment')],
    'card' => ['url' => env('CARD_PAYMENT_URL', 'https://secure.payment.com')],

    // Versements aux organisateurs (SHAP). Même règle que ci-dessus : déclaré
    // ici, lu par `config()`, jamais par `env()` depuis un service.
    'shap' => [
        'api_id' => env('API_PAYOUT_ID', ''),
        'api_secret' => env('API_PAYOUT_SECRET', ''),
        'base_url' => env('SHAP_BASE_URL', 'https://test.billing-easy.net/shap/api/v1/merchant/'),

        // Secret partagé attendu sur le rappel de versement, en en-tête
        // « X-Webhook-Secret » ou en paramètre « ?token= ». Sans lui, le
        // rappel est REFUSÉ : il fait sortir de l'argent, et le sondage
        // `payout:check-status` réconcilie de toute façon toutes les 5 min.
        'webhook_secret' => env('SHAP_WEBHOOK_SECRET'),
    ],

];
