<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CountriesSeeder::class,
            TaxClassSeeder::class,
            TaxJurisdictionSeeder::class,
            TaxDeductionClassSeeder::class,
            TaxReliefClassSeeder::class,
            TaxClassLinkSeeder::class,
            TaxRateSeeder::class,
            TaxReliefRuleSeeder::class,
            TaxVersionSeeder::class,
            TaxDeductionRuleSeeder::class,
            CountryTariffSeeder::class,
            StateTariffSeeder::class,
            LocalTariffSeeder::class,
            StateDeductionOverrideSeeder::class,
            VatDemoSeeder::class,
            TaxBootstrapSeeder::class,
            FxRateSeeder::class,
        ]);
    }
}
