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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'guzzle' => [
            'verify' => env('APP_ENV') === 'production' ? true : false,
        ],
    ],

    // CA certificate bundle for outgoing HTTPS calls (see AppServiceProvider).
    // Only needed when php.ini has no curl.cainfo; Laragon's bundle is found
    // automatically.
    'http' => [
        'ca_bundle' => env('HTTP_CA_BUNDLE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ABA PayWay (KHQR with callback)
    |--------------------------------------------------------------------------
    |
    | The live payment gateway, and the only one left: the Bakong integration it
    | replaced is gone, and the payments table keeps those older rows as history
    | nothing can be asked about. PayWay is not polled -- it POSTs to
    | PAYWAY_CALLBACK_URL when a payment lands, so there is no daily request
    | quota to run out of.
    |
    | Values come from ABA's merchant portal. Never commit them -- .env only.
    |
    */
    'payway' => [
        // Sandbox: https://checkout-sandbox.payway.com.kh
        // Live:    https://checkout.payway.com.kh
        'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'merchant_id' => env('PAYWAY_MERCHANT_ID'),
        // ABA calls this the "Public Key" / API key. Used as the HMAC-SHA512 secret.
        'api_key' => env('PAYWAY_API_KEY'),
        // Publicly reachable https URL of POST /payway/callback. PayWay cannot
        // reach localhost -- see PAYMENTS.md for the cloudflared/ngrok recipe.
        // Empty falls back to route('payway.callback'), which only works once
        // APP_URL is a public address.
        'callback_url' => env('PAYWAY_CALLBACK_URL'),
        'currency' => env('PAYWAY_CURRENCY', 'USD'),
        // Minutes a KHQR stays payable. PayWay's own minimum is 3.
        'qr_lifetime' => env('PAYWAY_QR_LIFETIME', 10),
        'timeout' => env('PAYWAY_TIMEOUT', 15),
    ],

];
