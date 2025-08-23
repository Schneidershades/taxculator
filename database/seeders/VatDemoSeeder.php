<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxJurisdiction;
use App\Models\VatVersion;
use App\Models\VatRate;

class VatDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a country-level NG jurisdiction exists
        $country = TaxJurisdiction::firstOrCreate(
            ['level' => 'country', 'country_code' => 'NG'],
            [] // keep attrs minimal to avoid mass-assignment surprises
        );

        // Create (or publish) a 2025 VAT version for NG
        $ver = VatVersion::updateOrCreate(
            ['tax_jurisdiction_id' => $country->id, 'tax_year' => 2025],
            ['status' => VatVersion::STATUS_PUBLISHED, 'effective_from' => '2025-01-01']
        );

        // Standard 7.5%, Zero, Exempt
        VatRate::updateOrCreate(['vat_version_id' => $ver->id, 'code' => 'standard'], [
            'name'       => 'Standard',
            'rate_type'  => 'percentage',
            'rate_value' => 7.5000,
        ]);
        VatRate::updateOrCreate(['vat_version_id' => $ver->id, 'code' => 'zero'], [
            'name'       => 'Zero rated',
            'rate_type'  => 'percentage',
            'rate_value' => 0.0000,
        ]);
        VatRate::updateOrCreate(['vat_version_id' => $ver->id, 'code' => 'exempt'], [
            'name'       => 'Exempt',
            'rate_type'  => 'percentage',
            'rate_value' => 0.0000,
        ]);
    }
}
