<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxVersion;
use App\Models\TaxClass;
use App\Models\ContributionRule;

class ContributionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Pick an existing published version (e.g., NG 2025)
        $v = TaxVersion::first();
        if (!$v) return;

        // Create a "Pension" rule: employee 8%, employer 10% on (basic + housing + clothing)
        $rule = ContributionRule::firstOrCreate([
            'tax_version_id' => $v->id,
            'name'           => 'Pension',
        ], [
            'base_type'      => 'classes',
            'rate_type'      => 'percentage',
            'employee_rate'  => 8.0000,
            'employer_rate'  => 10.0000,
            'combine_mode'   => 'stack',
        ]);

        $baseShorts = ['basic_salary', 'housing', 'clothing'];
        $classIds   = TaxClass::whereIn('short_name', $baseShorts)->pluck('id')->all();
        $rule->baseClasses()->sync($classIds);
    }
}
