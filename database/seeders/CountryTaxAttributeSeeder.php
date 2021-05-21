<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxAttribute;

class CountryTaxAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 1,
        	'require_deduction' => false,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 2,
        	'require_deduction' => true,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 3,
        	'require_deduction' => true,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 4,
        	'require_deduction' => false,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 5,
        	'require_deduction' => false,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 6,
        	'require_deduction' => false,
        ]);

        $class = CountryTaxAttribute::create([
        	'country_id' => 1,
        	'tax_attribute_id' => 7,
        	'require_deduction' => false,
        ]);
    }
}
