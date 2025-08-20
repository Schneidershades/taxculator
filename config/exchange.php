<?php

return [
    'providers' => [
        \App\Services\Currency\Providers\FlutterwaveProvider::class,
        // \App\Services\Currency\Providers\ExchangeratesApiProvider::class,
    ],
    'cache_ttl_hours' => 6,
];
