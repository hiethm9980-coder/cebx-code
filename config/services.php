<?php
return [
    'dhl' => [
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
        'base_url' => env('DHL_BASE_URL', 'https://express.api.dhl.com/mydhlapi'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'),
    ],
    'aramex' => [
        'username' => env('ARAMEX_USERNAME'),
        'password' => env('ARAMEX_PASSWORD'),
        'account_number' => env('ARAMEX_ACCOUNT_NUMBER'),
        'account_pin' => env('ARAMEX_ACCOUNT_PIN'),
    ],
    'smsa' => [
        'api_key' => env('SMSA_API_KEY'),
        'passkey' => env('SMSA_PASSKEY'),
    ],
    'fedex' => [
        'client_id' => env('FEDEX_CLIENT_ID'),
        'client_secret' => env('FEDEX_CLIENT_SECRET'),
    ],
    'moyasar' => [
        'api_key' => env('MOYASAR_API_KEY'),
        'secret_key' => env('MOYASAR_SECRET_KEY'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
];
