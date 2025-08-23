<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxJurisdiction;
use App\Models\CorporateTaxVersion;

class CorporateTaxDemoSeeder extends Seeder
{
    public function run(): void
    {
        $country = TaxJurisdiction::where('level', 'country')->where('country_code', 'NG')->first();
        if (!$country) return;

        CorporateTaxVersion::updateOrCreate([
            'tax_jurisdiction_id' => $country->id,
            'tax_year' => 2025,
        ], [
            'status'               => CorporateTaxVersion::STATUS_PUBLISHED,
            'rate_type'            => 'percentage',
            'rate_value'           => 30.0000,   // 30%
            'minimum_tax_amount'   => 0,
            'effective_from'       => '2025-01-01',
            'effective_to'         => null,
        ]);
    }
}
