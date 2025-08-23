<?php

namespace Database\Seeders;

use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use Illuminate\Database\Seeder;
use App\Models\WithholdingCredit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WithholdingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $country = TaxJurisdiction::where('level', 'country')->where('country_code', 'NG')->first();

        if (!$country) return;

        $rule = WithholdingRule::firstOrCreate([
            'tax_jurisdiction_id' => $country->id,
            'payee_type'   => 'individual',
            'income_type'  => 'contract',
            'effective_from' => '2025-01-01',
        ], [
            'rate'         => 5.0000,
            'min_amount'   => 0,
            'effective_to' => null,
        ]);

        // Credit for beneficiary_id=1 (adjust to your user id)
        WithholdingCredit::create([
            'beneficiary_id'    => 1,
            'withholding_rule_id' => $rule->id,
            'tax_year'          => 2025,
            'period'            => '2025-04',
            'base_amount'       => 1_000_000,
            'wht_amount'        => 50_000,
            'remaining_amount'  => 50_000,
        ]);
    }
}
