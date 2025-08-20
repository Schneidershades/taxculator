<?php

namespace Database\Seeders;

use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CountryTariffSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        $ng = TaxJurisdiction::country('NG')->firstOrFail();
        $vCountry = TaxVersion::where('tax_jurisdiction_id', $ng->id)->where('tax_year', $year)->firstOrFail();

        $rows = [
            ['min' => 0,       'max' => 300000,  'rate_type' => 'percentage', 'rate_value' => 7,  'ordering' => 1],
            ['min' => 300000,  'max' => 600000,  'rate_type' => 'percentage', 'rate_value' => 11, 'ordering' => 2],
            ['min' => 600000,  'max' => 1100000, 'rate_type' => 'percentage', 'rate_value' => 15, 'ordering' => 3],
            ['min' => 1100000, 'max' => 2700000, 'rate_type' => 'percentage', 'rate_value' => 19, 'ordering' => 4],
            ['min' => 2700000, 'max' => 4300000, 'rate_type' => 'percentage', 'rate_value' => 21, 'ordering' => 5],
            ['min' => 4300000, 'max' => null,    'rate_type' => 'percentage', 'rate_value' => 24, 'ordering' => 6],
        ];

        foreach ($rows as $r) {
            TaxTariff::updateOrCreate(
                ['tax_version_id' => $vCountry->id, 'ordering' => $r['ordering']],
                [
                    'bracket_min' => $r['min'],
                    'bracket_max' => $r['max'],
                    'rate_type' => $r['rate_type'],
                    'rate_value' => $r['rate_value']
                ]
            );
        }
    }
}
