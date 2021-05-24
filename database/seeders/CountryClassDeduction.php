<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryClassDeduction;

class CountryClassDeduction extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 1,
            'country_tax_class_id' => 1,
        ]);

        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 1,
            'country_tax_class_id' => 2,
        ]);

        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 1,
            'country_tax_class_id' => 3,
        ]);

        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 2,
            'country_tax_class_id' => 1,
        ]);

        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 2,
            'country_tax_class_id' => 2,
        ]);

        $class = CountryClassDeduction::create([
            'country_tax_deduction_class_id' => 2,
            'country_tax_class_id' => 3,
        ]);
    }
}
