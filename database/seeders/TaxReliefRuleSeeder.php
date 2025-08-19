<?php

namespace Database\Seeders;

use App\Models\TaxVersion;
use App\Models\TaxReliefRule;
use App\Models\TaxReliefClass;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxReliefRuleSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        $ng = TaxJurisdiction::country('NG')->firstOrFail();
        $vCountry = TaxVersion::where('tax_jurisdiction_id', $ng->id)->where('tax_year', $year)->firstOrFail();

        $craFixed = TaxReliefClass::where('code', 'CRA-2')->firstOrFail(); // below threshold
        $craPct   = TaxReliefClass::where('code', 'CRA-3')->firstOrFail(); // variable %

        TaxReliefRule::updateOrCreate(
            ['tax_version_id' => $vCountry->id, 'tax_relief_class_id' => $craFixed->id],
            [
                'relief_type' => 'amount',
                'value' => 200000,
                'minimum_amount' => 1,
                'maximum_amount' => 20000000,
                'minimum_status' => 'static',
                'maximum_status' => 'static',
                'combine_mode' => 'stack'
            ]
        );

        TaxReliefRule::updateOrCreate(
            ['tax_version_id' => $vCountry->id, 'tax_relief_class_id' => $craPct->id],
            [
                'relief_type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 0,
                'maximum_amount' => null,
                'minimum_status' => 'unlimited',
                'maximum_status' => 'unlimited',
                'combine_mode' => 'stack'
            ]
        );
    }
}
