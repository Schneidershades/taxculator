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
        	'code' => 'CRA',
        ]);
    }
}
