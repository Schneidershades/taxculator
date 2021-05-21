<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxAttribute;

class TaxAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = TaxAttribute::create([
        	'name' => 'basic_salary',
        ]);

        $class = TaxAttribute::create([
            'name' => 'housing',
        ]);

        $class = TaxAttribute::create([
            'name' => 'clothing',
        ]);

        $class = TaxAttribute::create([
            'name' => 'utility',
        ]);

        $class = TaxAttribute::create([
            'name' => 'launch',
        ]);

        $class = TaxAttribute::create([
            'name' => 'education',
        ]);

        $class = TaxAttribute::create([
            'name' => 'vacation',
        ]);
    }
}
