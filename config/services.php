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

    'hairisma' => [
        'url' => env('HAIRISMA_URL'),
        'timeout' => (int) env('HAIRISMA_TIMEOUT', 60),
    ],

    'ldap' => [
        'hosts' => env('LDAP_HOSTS'),
        'domain' => env('LDAP_DOMAIN', 'tabanankab.go.id'),
        'timeout' => (int) env('LDAP_TIMEOUT', 5),
    ],

    'sso' => [
        'base_url' => env('SSO_BASE_URL'),
        'jwks_url' => env('SSO_JWKS_URL'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri' => env('SSO_REDIRECT_URI'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    'push_relay' => [
        'url' => env('PUSH_RELAY_URL'),
        'secret' => env('PUSH_RELAY_SECRET'),
    ],

    'mobile' => [
        'access_minutes' => (int) env('MOBILE_ACCESS_MINUTES', 1440),
        // Aplikasi Android/iOS tetap masuk sampai pengguna logout (refresh token diperpanjang setiap dipakai).
        'refresh_days' => (int) env('MOBILE_REFRESH_DAYS', 3650),
        'android_url' => env('MOBILE_ANDROID_URL', 'https://customer-assets-4nw71qhi.emergentagent.net/job_surat-tabanan--5h3gshgt/artifacts/c10c6b34818b6d2e_E-Surat%20Tabanan_v1.0.0.apk'),
        'ios_url' => env('MOBILE_IOS_URL'),
    ],

];
