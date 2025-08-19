<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use App\Models\TaxDeductionRule;
use App\Models\TaxDeductionClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxDeductionRuleSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        // Find versions
        $ng = TaxJurisdiction::country('NG')->firstOrFail();
        $vCountry = TaxVersion::where('tax_jurisdiction_id', $ng->id)->where('tax_year', $year)->firstOrFail();

        $pension = TaxDeductionClass::where('short_name', 'pension')->firstOrFail();
        $nhf     = TaxDeductionClass::where('short_name', 'nhf')->firstOrFail();
        $life    = TaxDeductionClass::where('short_name', 'life_insurance')->firstOrFail();

        // Pension 8% of (basic_salary + housing + clothing) — stackable by default
        $r1 = TaxDeductionRule::updateOrCreate(
            ['tax_version_id' => $vCountry->id, 'tax_deduction_class_id' => $pension->id],
            ['deduction_type' => 'percentage', 'value' => 8, 'combine_mode' => 'stack']
        );
        $r1->baseClasses()->sync(
            TaxClass::whereIn('short_name', ['basic_salary', 'housing', 'clothing'])->pluck('id')->all()
        );

        // NHF 2.5% of same base
        $r2 = TaxDeductionRule::updateOrCreate(
            ['tax_version_id' => $vCountry->id, 'tax_deduction_class_id' => $nhf->id],
            ['deduction_type' => 'percentage', 'value' => 2.5, 'combine_mode' => 'stack']
        );
        $r2->baseClasses()->sync(
            TaxClass::whereIn('short_name', ['basic_salary', 'housing', 'clothing'])->pluck('id')->all()
        );

        // Life insurance flat amount (placeholder)
        TaxDeductionRule::updateOrCreate(
            ['tax_version_id' => $vCountry->id, 'tax_deduction_class_id' => $life->id],
            ['deduction_type' => 'amount', 'value' => 0, 'combine_mode' => 'stack']
        );
    }
}
