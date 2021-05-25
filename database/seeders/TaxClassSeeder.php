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
            'short_name' => 'basic_salary',
        ]);

        $class = TaxClass::create([
            'name' => 'Housing',
            'short_name' => 'housing',
        ]);

        $class = TaxClass::create([
            'name' => 'Clothing',
            'short_name' => 'clothing',
        ]);

        $class = TaxClass::create([
            'name' => 'Utility',
            'short_name' => 'utility',
        ]);

        $class = TaxClass::create([
            'name' => 'Lunch',
            'short_name' => 'lunch',
        ]);

        $class = TaxClass::create([
            'name' => 'Education',
            'short_name' => 'education',
        ]);

        $class = TaxClass::create([
            'name' => 'Vacation',
            'short_name' => 'vacation',
        ]);
    }
}
