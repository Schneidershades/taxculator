<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxClass;

class TaxClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = TaxClass::create([
        	'name' => 'Basic Salary',
        ]);

        $class = TaxClass::create([
            'name' => 'Housing',
        ]);

        $class = TaxClass::create([
            'name' => 'Clothing',
        ]);

        $class = TaxClass::create([
            'name' => 'Utility',
        ]);

        $class = TaxClass::create([
            'name' => 'Lunch',
        ]);

        $class = TaxClass::create([
            'name' => 'Education',
        ]);

        $class = TaxClass::create([
            'name' => 'Vacation',
        ]);
    }
}
