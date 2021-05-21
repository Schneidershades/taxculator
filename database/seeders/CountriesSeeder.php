<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = Country::create([
        	'name' => 'Nigeria',
            'code' => 'NG',
            'short_name' => 'NG',
            'currency' => '₦',
            'currency_code' => '₦',
        ]);
    }
}
