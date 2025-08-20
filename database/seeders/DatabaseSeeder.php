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
        $this->call(CountriesSeeder::class);
        $this->call(TaxClassSeeder::class);
        $this->call(TaxJurisdictionSeeder::class);
        $this->call(TaxDeductionClassSeeder::class);
        $this->call(TaxReliefClassSeeder::class);
        $this->call(TaxClassLinkSeeder::class);
        $this->call(TaxRateSeeder::class);
        $this->call(TaxReliefRuleSeeder::class);
        $this->call(TaxVersionSeeder::class);
        $this->call(TaxDeductionRuleSeeder::class);
        $this->call(CountryTariffSeeder::class);
        $this->call(StateTariffSeeder::class);
        $this->call(LocalTariffSeeder::class);
        $this->call(StateDeductionOverrideSeeder::class);
    }
}
