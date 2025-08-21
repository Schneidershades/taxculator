<?php

namespace App\Classes;

use App\Services\Currency\CountryCurrencyMap;
use App\Services\Currency\CurrencyService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PriceCast implements CastsAttributes
{
    /**
     * @param  mixed  $value  The raw USD amount from the database
     * @param  array  $attributes  All model attributes, so we can read 'collocation_percentage'
     * @return float The final, localized price
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $country = optional(request()->user()?->country)->iso2 ?? session('country_iso2', 'NG');
        $currency = CountryCurrencyMap::forCountry($country);
        $localized = CurrencyService::convertUsdTo($value, $currency);

        return round($localized, 2);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return [$key => $value];
    }
}
