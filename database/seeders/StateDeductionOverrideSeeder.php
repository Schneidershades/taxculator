<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use App\Models\TaxDeductionRule;
use App\Models\TaxDeductionClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateDeductionOverrideSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        $lag = TaxJurisdiction::state('NG', 'LA')->firstOrFail();
        $vState = TaxVersion::where('tax_jurisdiction_id', $lag->id)->where('tax_year', $year)->firstOrFail();

        $nhf = TaxDeductionClass::where('short_name', 'nhf')->firstOrFail();

        $r = TaxDeductionRule::updateOrCreate(
            ['tax_version_id' => $vState->id, 'tax_deduction_class_id' => $nhf->id],
            ['deduction_type' => 'percentage', 'value' => 2.0, 'combine_mode' => 'override']
        );
        $r->baseClasses()->sync(
            TaxClass::whereIn('short_name', ['basic_salary', 'housing', 'clothing'])->pluck('id')->all()
        );
    }
}
