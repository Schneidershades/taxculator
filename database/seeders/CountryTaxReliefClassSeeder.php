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
            'value' => 1,
            'minimum_amount' => 20000001,
            'maximum_amount' => 0,
            'minimum_status' => 'static',
            'maximum_status' => 'unlimited',
        ]);

        $class = CountryTaxReliefClass::create([
            'country_id' => 1,
            'tax_relief_class_id' => 2,
            'relief_type' => 'amount',
            'value' => 200000,
            'minimum_amount' => 1,
            'maximum_amount' => 20000000,
            'minimum_status' => 'static',
            'maximum_status' => 'static',
        ]);

        $class = CountryTaxReliefClass::create([
            'country_id' => 1,
            'tax_relief_class_id' => 3,
            'relief_type' => 'percentage',
            'value' => 20,
            'minimum_amount' => 1,
            'minimum_amount' => 0,
            'minimum_status' => 'unlimited',
            'maximum_status' => 'unlimited',
        ]);
    }
}
