<?php

namespace Database\Seeders;

use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LocalTariffSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        $ikeja = TaxJurisdiction::local('NG', 'LA', 'IKEJA')->firstOrFail();
        $vLocal = TaxVersion::where('tax_jurisdiction_id', $ikeja->id)->where('tax_year', $year)->firstOrFail();

        TaxTariff::updateOrCreate(
            ['tax_version_id' => $vLocal->id, 'ordering' => 1],
            ['bracket_min' => 0, 'bracket_max' => null, 'rate_type' => 'percentage', 'rate_value' => 0.5]
        );
    }
}
