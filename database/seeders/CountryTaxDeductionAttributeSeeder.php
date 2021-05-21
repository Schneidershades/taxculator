<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxDeductionAttribute;

class CountryTaxDeductionAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxDeductionAttribute::create([
        	'country_id' => 1,
        	'tax_deduction_attribute_id' => 1,
        	'deduction_type' => 'percentage',
            'value' => 8,
        ]);

        $class = CountryTaxDeductionAttribute::create([
        	'country_id' => 1,
        	'tax_deduction_attribute_id' => 2,
            'deduction_type' => 'percentage',
            'value' => 2.5,
        ]);

        $class = CountryTaxDeductionAttribute::create([
        	'country_id' => 1,
        	'tax_deduction_attribute_id' => 3,
            'name' => 'life_insurance',
            'deduction_type' => 'amount',
            'value' => 5000,
        ]);
    }
}
