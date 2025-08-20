<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxJurisdiction;
use App\Models\TaxVersion;
use App\Models\TaxClass;
use App\Models\TaxClassLink;
use App\Models\TaxDeductionClass;
use App\Models\TaxDeductionRule;
use App\Models\TaxReliefClass;
use App\Models\TaxReliefRule;
use App\Models\TaxTariff;

class TaxRateSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'NG' => [
                'year' => 2025,
                'classes' => [
                    ['name' => 'Basic Salary', 'short_name' => 'basic_salary'],
                    ['name' => 'Housing',      'short_name' => 'housing'],
                    ['name' => 'Clothing',     'short_name' => 'clothing'],
                    // add more if you need...
                ],
                'deductions' => [
                    ['name' => 'Pension',         'short_name' => 'pension',        'type' => 'percentage', 'value' => 8,   'base' => ['basic_salary', 'housing', 'clothing'], 'combine_mode' => 'stack'],
                    ['name' => 'NHF',             'short_name' => 'nhf',            'type' => 'percentage', 'value' => 2.5, 'base' => ['basic_salary', 'housing', 'clothing'], 'combine_mode' => 'stack'],
                    ['name' => 'Life Insurance',  'short_name' => 'life_insurance', 'type' => 'amount',     'value' => 0,                                            'combine_mode' => 'stack'],
                ],
                'reliefs' => [
                    ['code' => 'CRA-1', 'type' => 'fixed',    'relief_type' => 'percentage', 'value' => 1,      'min' => 20000001, 'max' => null,     'min_status' => 'static',    'max_status' => 'unlimited', 'combine_mode' => 'stack'],
                    ['code' => 'CRA-2', 'type' => 'fixed',    'relief_type' => 'amount',     'value' => 200000, 'min' => 1,        'max' => 20000000, 'min_status' => 'static',    'max_status' => 'static',    'combine_mode' => 'stack'],
                    ['code' => 'CRA-3', 'type' => 'variable', 'relief_type' => 'percentage', 'value' => 20,     'min' => 0,        'max' => null,     'min_status' => 'unlimited', 'max_status' => 'unlimited', 'combine_mode' => 'stack'],
                ],
                'tariffs' => [
                    ['min' => 0,       'max' => 300000,  'rate_type' => 'percentage', 'rate_value' => 7,  'ordering' => 1],
                    ['min' => 300000,  'max' => 600000,  'rate_type' => 'percentage', 'rate_value' => 11, 'ordering' => 2],
                    ['min' => 600000,  'max' => 1100000, 'rate_type' => 'percentage', 'rate_value' => 15, 'ordering' => 3],
                    ['min' => 1100000, 'max' => 2700000, 'rate_type' => 'percentage', 'rate_value' => 19, 'ordering' => 4],
                    ['min' => 2700000, 'max' => 4300000, 'rate_type' => 'percentage', 'rate_value' => 21, 'ordering' => 5],
                    ['min' => 4300000, 'max' => null,    'rate_type' => 'percentage', 'rate_value' => 24, 'ordering' => 6],
                ],
            ],
        ];

        foreach ($data as $countryCode => $cfg) {
            // 1) Jurisdiction (country level)
            $jurisdiction = TaxJurisdiction::updateOrCreate(
                ['level' => 'country', 'country_code' => strtoupper($countryCode), 'state_code' => null, 'local_code' => null],
                ['name' => $countryCode, 'currency_code' => 'NGN', 'parent_id' => null]
            );

            // 2) Version
            $ver = TaxVersion::updateOrCreate(
                ['tax_jurisdiction_id' => $jurisdiction->id, 'tax_year' => $cfg['year']],
                ['effective_from' => "{$cfg['year']}-01-01", 'effective_to' => null]
            );

            // 3) Classes
            foreach ($cfg['classes'] as $c) {
                TaxClass::updateOrCreate(
                    ['short_name' => $c['short_name']],
                    ['name' => $c['name']]
                );
            }
            $classIdByShort = TaxClass::pluck('id', 'short_name'); // ['basic_salary' => 1, ...]

            // 4) Link classes to this version
            foreach ($classIdByShort as $short => $classId) {
                TaxClassLink::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_class_id' => $classId],
                    ['require_deduction' => in_array($short, ['housing', 'clothing'])]
                );
            }

            // 5) Deductions (rules)
            foreach ($cfg['deductions'] as $d) {
                $dc = TaxDeductionClass::updateOrCreate(
                    ['short_name' => $d['short_name']],
                    ['name' => $d['name']]
                );

                $rule = TaxDeductionRule::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_deduction_class_id' => $dc->id],
                    [
                        'deduction_type' => $d['type'],
                        'value'         => $d['value'],
                        'combine_mode'  => $d['combine_mode'] ?? 'stack',
                    ]
                );

                // base classes (for percentage rules)
                if (!empty($d['base'])) {
                    $baseIds = collect($d['base'])
                        ->map(fn($short) => $classIdByShort[$short] ?? null)
                        ->filter()
                        ->values()
                        ->all();

                    $rule->baseClasses()->sync($baseIds);
                } else {
                    $rule->baseClasses()->sync([]); // clear if none
                }
            }

            // 6) Reliefs (rules)
            foreach ($cfg['reliefs'] as $r) {
                $reliefClass = TaxReliefClass::updateOrCreate(
                    ['code' => $r['code']],
                    ['name' => 'Consolidated Relief Allowance', 'type' => $r['type']]
                );

                TaxReliefRule::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'tax_relief_class_id' => $reliefClass->id],
                    [
                        'relief_type'     => $r['relief_type'],
                        'value'           => $r['value'],
                        'minimum_amount'  => $r['min'] ?? 0,
                        'maximum_amount'  => $r['max'] ?? null,
                        'minimum_status'  => $r['min_status'],
                        'maximum_status'  => $r['max_status'],
                        'combine_mode'    => $r['combine_mode'] ?? 'stack',
                    ]
                );
            }

            // 7) Tariffs (brackets)
            foreach ($cfg['tariffs'] as $t) {
                TaxTariff::updateOrCreate(
                    ['tax_version_id' => $ver->id, 'ordering' => $t['ordering']],
                    [
                        'bracket_min' => $t['min'],
                        'bracket_max' => $t['max'],
                        'rate_type'   => $t['rate_type'],
                        'rate_value'  => $t['rate_value'],
                    ]
                );
            }
        }
    }
}
