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
        $this->call(TaxAttributesSeeder::class);
        $this->call(TaxDeductionAttributesSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(CountryTaxAttributesSeeder::class);
        $this->call(CountryTaxDeductionAttributesSeeder::class);
    }
}
