<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxClass;

class CountryTaxClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 1,
            'require_deduction' => false,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 2,
            'require_deduction' => true,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 3,
            'require_deduction' => true,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 4,
            'require_deduction' => false,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 5,
            'require_deduction' => false,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 6,
            'require_deduction' => false,
        ]);

        $class = CountryTaxClass::create([
            'country_id' => 1,
            'tax_class_id' => 7,
            'require_deduction' => false,
        ]);
    }
}
