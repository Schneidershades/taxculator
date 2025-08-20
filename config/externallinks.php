<?php

return [
    'user_email_verify_url' => env('APP_ENV') == 'local' ? env('USER_EMAIL_VERIFY_URL') : env('USER_EMAIL_VERIFY_URL'),
    'deepgram_token' => env('DEEPGRAM_TOKEN') == 'local' ? env('DEEPGRAM_TOKEN') : env('DEEPGRAM_TOKEN'),
    'user_email_reset_url' => env('APP_ENV') == 'local' ? env('USER_EMAIL_RESET_URL') : env('USER_EMAIL_RESET_URL'),
    'stripe_checkout_success_url' => env('APP_ENV') == 'local' ? env('STRIPE_CHECKOUT_SUCCESS_URL') : env('STRIPE_CHECKOUT_SUCCESS_URL'),
    'stripe_checkout_cancel_url' => env('APP_ENV') == 'local' ? env('STRIPE_CHECKOUT_CANCEL_URL') : env('STRIPE_CHECKOUT_CANCEL_URL'),
    'stripe_webhook_secret' => env('APP_ENV') == 'local' ? env('STRIPE_WEBHOOK_SECRET') : env('STRIPE_WEBHOOK_SECRET'),
    'test_mode' => env('TEST_MODE'),
    'clockman_userId' => env('CLOCKMAN_USERID'),
    'clockman_conversationId' => env('CLOCKMAN_CONVERSATIONID'),
    'clockman_secretKey' => env('CLOCKMAN_SECRETKEY'),
    'clockman_format' => env('CLOCKMAN_FORMAT'),
    'wificall_api' => env('WIFICALL_API_URL') == 'local' ? env('WIFICALL_API_URL') : env('WIFICALL_API_URL'),

    'termii_api_key' => env('TERMII_API_KEY'),
    'termii_api_url' => env('TERMII_API_URL'),

    'sendchamp_api_url' => env('SENDCHAMP_API_KEY'),

    'paystack_url' => env('PAYSTACK_PAYMENT_URL'),
    'paystack_public_key' => env('TEST_MODE') == 'test' ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY'),
    'paystack_secret_key' => env('TEST_MODE') == 'test' ? env('PAYSTACK_TEST_SECRET_KEY') : env('PAYSTACK_LIVE_SECRET_KEY'),

    'flutterwave_base_url_v3' => 'https://api.flutterwave.com/v3',
    'flutterwave_public_key' => env('TEST_MODE') == 'test' ? env('FLUTTERWAVE_TEST_PUBLIC_KEY') : env('FLUTTERWAVE_LIVE_PUBLIC_KEY'),
    'flutterwave_secret_key' => env('TEST_MODE') == 'test' ? env('FLUTTERWAVE_TEST_SECRET_KEY') : env('FLUTTERWAVE_LIVE_SECRET_KEY'),
    'flutterwave_encryption_key' => env('TEST_MODE') == 'test' ? env('FLUTTERWAVE_TEST_ENCRYPTION_KEY') : env('FLUTTERWAVE_LIVE_ENCRYPTION_KEY'),

    'webhook_url' => env('PAYSTACK_WEBHOOK_URL'),
    'convert_bet_codes_api_key' => env('TEST_MODE') == 'test' ? env('CONVERT_BET_CODES_TEST_API_KEY') : env('CONVERT_BET_CODES_LIVE_API_KEY'),

    'exchange_rates_api_key' => env('EXCHANGERATES_API_KEY'),
    'exchange_rates_api_base_url' => 'https://api.exchangeratesapi.io/v1',

];
