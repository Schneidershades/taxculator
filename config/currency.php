<?php

return [
    'special_countries' => [
        'NG' => 'NGN', // Nigeria
        'AE' => 'AED', // United Arab Emirates
    ],

    'supported_currencies' => ['USD', 'NGN', 'AED'],

    'default_currency' => 'USD',

    'fallback_rates' => [
        'USD' => 1.0,
    ],

    'auto_relock' => env('CART_AUTO_RELOCK', true),
];
