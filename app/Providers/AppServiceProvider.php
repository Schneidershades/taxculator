<?php

namespace App\Providers;

use App\Models\TaxClass;
use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxClassLink;
use App\Models\TaxReliefRule;
use App\Models\ContributionRule;
use App\Models\TaxDeductionRule;
use App\Observers\TaxRuleObserver;
use App\Models\CountryTaxReliefClass;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\CountryTaxDeductionClass;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        TaxTariff::observe(new class {
            use TaxRuleObserver;
        });
        TaxReliefRule::observe(new class {
            use TaxRuleObserver;
        });
        TaxDeductionRule::observe(new class {
            use TaxRuleObserver;
        });
        ContributionRule::observe(new class {
            use TaxRuleObserver;
        });
        TaxClassLink::observe(new class {
            use TaxRuleObserver;
        });
        TaxVersion::observe(new class {
            use TaxRuleObserver;
        });

        RateLimiter::for('tax-calc', function (Request $request) {
            // 60/min per IP; tune as needed
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
