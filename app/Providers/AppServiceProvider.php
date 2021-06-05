<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use App\Models\TaxClass;
use App\Models\CountryTaxDeductionClass;
use App\Models\CountryTaxReliefClass;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Schema::defaultStringLength(191);

        Relation::morphMap([
            'taxClass' => TaxClass::class,
            'countryTaxDeductionClass' => CountryTaxDeductionClass::class,
            'countryTaxReliefClass' => CountryTaxReliefClass::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
}
