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

    'iyzico' => [
        'api_key' => env('IYZI_API_KEY', ''),
        'secret_key' => env('IYZI_SECRET_KEY', ''),
        'base_url' => env('IYZI_BASE_URL', 'https://sandbox-api.iyzipay.com'),
        // Merchant id (MID), needed to verify X-IYZ-SIGNATURE-V3 on subscription webhooks
        'merchant_id' => env('IYZI_MERCHANT_ID'),
        // Optional: reuse an existing iyzico subscription product instead of auto-creating one
        'subscription_product_ref' => env('IYZI_SUBSCRIPTION_PRODUCT_REF'),
        // Our own signing secret for manual/internal webhook calls (X-Signature header).
        // Read via config so it also works when the config cache is active (env() would be null).
        'webhook_secret' => env('HMAC_WEBHOOK_SECRET'),
    ],

];
