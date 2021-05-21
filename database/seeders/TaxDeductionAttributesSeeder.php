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
        	'name' => 'pension',
        	'deduction_type' => 'percentage',
            'value' => 8,
        ]);

        $class = TaxDeductionAttribute::create([
            'name' => 'nhf',
            'deduction_type' => 'percentage',
            'value' => 2.5,
        ]);

        $class = TaxDeductionAttribute::create([
            'name' => 'life_insurance',
            'deduction_type' => 'amount',
            'value' => 5000,
        ]);
    }
}
