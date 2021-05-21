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
        $this->call(TaxClassSeeder::class);
        $this->call(TaxDeductionClassSeeder::class);
        $this->call(TaxReliefClassSeeder::class);
        $this->call(CountriesSeeder::class);
        $this->call(CountryTaxClassSeeder::class);
        $this->call(CountryTaxDeductionClassSeeder::class);
    }
}
