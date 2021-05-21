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
        	'name' => 'basic_salary',
        ]);

        $class = TaxClass::create([
            'name' => 'housing',
        ]);

        $class = TaxClass::create([
            'name' => 'clothing',
        ]);

        $class = TaxClass::create([
            'name' => 'utility',
        ]);

        $class = TaxClass::create([
            'name' => 'launch',
        ]);

        $class = TaxClass::create([
            'name' => 'education',
        ]);

        $class = TaxClass::create([
            'name' => 'vacation',
        ]);
    }
}
