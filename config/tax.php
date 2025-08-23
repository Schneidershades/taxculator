<?php

return [
    // Where your inventory ships from by default (fulfillment country)
    'origin_country' => env('TAX_ORIGIN_COUNTRY', 'AE'), // UAE

    // Default incoterm for cross-border goods:
    // - DAP (a.k.a. DDU): buyer pays import VAT/duties to customs → you don’t collect at checkout
    // - DDP           : you, the seller, are importer → you DO collect import VAT/duties at checkout
    'default_incoterm' => env('TAX_DEFAULT_INCOTERM', 'DAP'), // 'DDP' or 'DAP'

    // If you always sell digital services to specific countries AND must collect their VAT (e.g., NG),
    // you can rely on your CountryTaxRate table. No special list needed here.

    // For edge cases, force DDP by destination country
    'force_ddp_countries' => [
        // 'NG', // uncomment if you WANT to collect Nigeria import VAT yourself
    ],

    'cache_enabled' => env('TAX_CACHE_ENABLED', true),
    'cache_ttl'     => (int) env('TAX_CACHE_TTL', 86400), // 1 day
];
