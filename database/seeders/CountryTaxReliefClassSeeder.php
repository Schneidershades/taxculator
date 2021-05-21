<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxReliefClass;

class CountryTaxReliefClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxReliefClass::create([
        	'country_id' => 1,
        	'tax_relief_class_id' => 1,
        	'relief_type' => 'percentage',
            'value' => 20,
            'minimum_amount' => 20000000,
        ]);
    }
}
