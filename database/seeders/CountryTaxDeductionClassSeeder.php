<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxDeductionClass;

class CountryTaxDeductionClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxDeductionClass::create([
        	'country_id' => 1,
        	'tax_deduction_class_id' => 1,
        	'deduction_type' => 'percentage',
            'value' => 8,
        ]);

        $class = CountryTaxDeductionClass::create([
        	'country_id' => 1,
        	'tax_deduction_class_id' => 2,
            'deduction_type' => 'percentage',
            'value' => 2.5,
        ]);

        $class = CountryTaxDeductionClass::create([
        	'country_id' => 1,
        	'tax_deduction_class_id' => 3,
            'deduction_type' => 'amount',
            'value' => 5000,
        ]);
    }
}
