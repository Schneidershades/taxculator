<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxDeductionAttribute;

class TaxDeductionAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = TaxDeductionAttribute::create([
        	'name' => 'Pension',
            'short_name' => 'pension',
        ]);

        $class = TaxDeductionAttribute::create([
            'name' => 'National Housing Fund',
            'short_name' => 'NHF',
        ]);

        $class = TaxDeductionAttribute::create([
            'name' => 'Life Insurance',
            'short_name' => 'life_insurance',
        ]);
    }
}
