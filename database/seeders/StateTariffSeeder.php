<?php

namespace Database\Seeders;

use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateTariffSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        $lag = TaxJurisdiction::state('NG', 'LA')->firstOrFail();
        $vState = TaxVersion::where('tax_jurisdiction_id', $lag->id)->where('tax_year', $year)->firstOrFail();

        TaxTariff::updateOrCreate(
            ['tax_version_id' => $vState->id, 'ordering' => 1],
            ['bracket_min' => 0, 'bracket_max' => null, 'rate_type' => 'percentage', 'rate_value' => 1.5]
        );
    }
}
