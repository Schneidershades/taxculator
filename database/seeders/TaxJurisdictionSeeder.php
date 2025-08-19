<?php

namespace Database\Seeders;

use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxJurisdictionSeeder extends Seeder
{
    public function run()
    {
        // Country: Nigeria
        $ng = TaxJurisdiction::updateOrCreate(
            ['level' => 'country', 'country_code' => 'NG', 'state_code' => null, 'local_code' => null],
            ['name' => 'Nigeria', 'currency_code' => 'NGN', 'parent_id' => null]
        );

        // State: Lagos
        $lag = TaxJurisdiction::updateOrCreate(
            ['level' => 'state', 'country_code' => 'NG', 'state_code' => 'LA', 'local_code' => null],
            ['name' => 'Lagos State', 'currency_code' => 'NGN', 'parent_id' => $ng->id]
        );

        // Local: Ikeja LGA
        TaxJurisdiction::updateOrCreate(
            ['level' => 'local', 'country_code' => 'NG', 'state_code' => 'LA', 'local_code' => 'IKEJA'],
            ['name' => 'Ikeja LGA', 'currency_code' => 'NGN', 'parent_id' => $lag->id]
        );
    }
}
