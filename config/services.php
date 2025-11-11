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

    'django' => [
        'base_url' => env('VITE_DJANGO_BASEURL'),
        'hermes_base_url' => env('VITE_HERMES_BASEURL'),
        'api_user' => env('API_USER'),
        'api_password' => env('API_PASSWORD'),
    ],

    'php' => [
        'base_url' => env('PHP_BASE_URL'),
        'api_user' => env('PHP_AUTH_USER'),
        'api_password' => env('PHP_AUTH_PASSWORD'),
    ],
    
    'appkey' => env('LARAVEL_APP_KEY'),

    'oidc' => [
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI'),

        'authorize_url' => env('OIDC_AUTH_URL'),
        'token_url' => env('OIDC_TOKEN_URL'),
        'userinfo_url' => env('OIDC_USERINFO_URL')
    ],


];
