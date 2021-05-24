<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxReliefClass;

class TaxReliefClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = TaxReliefClass::create([
        	'name' => 'Consolidated Relief Allowance',
            'description' => 'Consolidated Relief Allowance for Gross Income above minimum amount',
        	'code' => 'CRA-1',
            'type' => 'fixed',
        ]);

        $class = TaxReliefClass::create([
            'name' => 'Consolidated Relief Allowance',
            'description' => 'Consolidated Relief Allowance for Gross Income below minimum amount',
            'code' => 'CRA-2',
            'type' => 'fixed',
        ]);

        $class = TaxReliefClass::create([
            'name' => 'Consolidated Relief Allowance',
            'description' => 'Consolidated Relief Allowance for Gross Income variable percentage',
            'code' => 'CRA-3',
            'type' => 'variable',
        ]);
    }
}
