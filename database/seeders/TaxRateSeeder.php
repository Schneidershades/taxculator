<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxReliefClass;
use App\Models\CountryTaxClass;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use App\Models\TaxDeductionClass;
use App\Models\CountryTaxReliefClass;
use App\Models\CountryTaxDeductionClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxRateSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'NG' => [
                'year' => 2025,
                'classes' => [
                    ['name' => 'Basic Salary', 'short_name' => 'basic_salary'],
                    ['name' => 'Housing', 'short_name' => 'housing'],
                    ['name' => 'Clothing', 'short_name' => 'clothing'],
                    // ...
                ],
                'deductions' => [
                    ['name' => 'Pension', 'short_name' => 'pension', 'type' => 'percentage', 'value' => 8, 'base' => ['basic_salary', 'housing', 'clothing']],
                    ['name' => 'NHF', 'short_name' => 'nhf', 'type' => 'percentage', 'value' => 2.5, 'base' => ['basic_salary', 'housing', 'clothing']],
                    ['name' => 'Life Insurance', 'short_name' => 'life_insurance', 'type' => 'amount', 'value' => 0],
                ],
                'reliefs' => [
                    // examples
                    ['relief_type' => 'percentage', 'value' => 1, 'min' => 20000001, 'max' => null, 'min_status' => 'static', 'max_status' => 'unlimited', 'code' => 'CRA-1', 'type' => 'fixed'],
                    ['relief_type' => 'amount', 'value' => 200000, 'min' => 1, 'max' => 20000000, 'min_status' => 'static', 'max_status' => 'static', 'code' => 'CRA-2', 'type' => 'fixed'],
                    ['relief_type' => 'percentage', 'value' => 20, 'min' => 0, 'max' => null, 'min_status' => 'unlimited', 'max_status' => 'unlimited', 'code' => 'CRA-3', 'type' => 'variable'],
                ],
                'tariffs' => [
                    ['min' => 0, 'max' => 300000, 'rate_type' => 'percentage', 'rate_value' => 7, 'ordering' => 1],
                    ['min' => 300000, 'max' => 600000, 'rate_type' => 'percentage', 'rate_value' => 11, 'ordering' => 2],
                    ['min' => 600000, 'max' => 1100000, 'rate_type' => 'percentage', 'rate_value' => 15, 'ordering' => 3],
                    ['min' => 1100000, 'max' => 2700000, 'rate_type' => 'percentage', 'rate_value' => 19, 'ordering' => 4],
                    ['min' => 2700000, 'max' => 4300000, 'rate_type' => 'percentage', 'rate_value' => 21, 'ordering' => 5],
                    ['min' => 4300000, 'max' => null, 'rate_type' => 'percentage', 'rate_value' => 24, 'ordering' => 6],
                ],
            ],
            // Add GH/KE/etc similarly
        ];

        foreach ($data as $cc => $cfg) {
            $jur = TaxJurisdiction::firstOrCreate(['country_code' => $cc], ['name' => $cc]);
            $ver = TaxVersion::updateOrCreate(
                ['tax_jurisdiction_id' => $jur->id, 'tax_year' => $cfg['year']],
                ['effective_from' => "$cfg[year]-01-01", 'effective_to' => null]
            );

            foreach ($cfg['classes'] as $c) {
                TaxClass::updateOrCreate(['short_name' => $c['short_name']], ['name' => $c['name']]);
            }

            foreach ($cfg['deductions'] as $d) {
                $dc = TaxDeductionClass::updateOrCreate(['short_name' => $d['short_name']], ['name' => $d['name']]);
                $rule = CountryTaxDeductionClass::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_deduction_class_id' => $dc->id],
                    ['deduction_type' => $d['type'], 'value' => $d['value']]
                );
                if (!empty($d['base'])) {
                    $classIds = TaxClass::whereIn('short_name', $d['base'])->pluck('id');
                    $countryTaxClasses = CountryTaxClass::where('tax_version_id', $ver->id)
                        ->whereIn('tax_class_id', $classIds)->pluck('id');
                    $rule->countryTaxClasses()->sync($countryTaxClasses);
                }
            }

            foreach ($cfg['reliefs'] as $r) {
                $base = TaxReliefClass::updateOrCreate(['code' => $r['code']], [
                    'name' => 'Consolidated Relief Allowance',
                    'type' => $r['type']
                ]);
                CountryTaxReliefClass::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_relief_class_id' => $base->id],
                    [
                        'relief_type' => $r['relief_type'],
                        'value' => $r['value'],
                        'minimum_amount' => $r['min'] ?? 0,
                        'maximum_amount' => $r['max'] ?? 0,
                        'minimum_status' => $r['min_status'],
                        'maximum_status' => $r['max_status'],
                    ]
                );
            }

            foreach ($cfg['tariffs'] as $t) {
                TaxTariff::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'ordering' => $t['ordering']],
                    [
                        'bracket_min' => $t['min'],
                        'bracket_max' => $t['max'],
                        'rate_type' => $t['rate_type'],
                        'rate_value' => $t['rate_value'],
                    ]
                );
            }

            // Link all classes to version
            $classIds = TaxClass::all()->pluck('id');
            foreach ($classIds as $cid) {
                CountryTaxClass::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_class_id' => $cid],
                    ['require_deduction' => in_array(TaxClass::find($cid)->short_name, ['housing', 'clothing'])]
                );
            }
        }
    }
}
