<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxDeductionClass;

class TaxDeductionClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = TaxDeductionClass::create([
        	'name' => 'Pension',
            'short_name' => 'pension',
            'unit' => 1,
        ]);

        $class = TaxDeductionClass::create([
            'name' => 'National Housing Fund',
            'short_name' => 'nhf',
            'unit' => 1,
        ]);

        $class = TaxDeductionClass::create([
            'name' => 'Life Insurance',
            'short_name' => 'life_insurance',
            'unit' => 1,
        ]);
    }
}
