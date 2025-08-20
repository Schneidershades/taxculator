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
    public function run(): void
    {
        $data = [
            'NG' => [
                'year' => 2025,
                'currency' => 'NGN',
                'classes' => [
                    ['name' => 'Basic Salary', 'short_name' => 'basic_salary'],
                    ['name' => 'Housing',      'short_name' => 'housing'],
                    ['name' => 'Clothing',     'short_name' => 'clothing'],
                    ['name' => 'Utility',      'short_name' => 'utility'],
                    ['name' => 'Lunch',        'short_name' => 'lunch'],
                    ['name' => 'Education',    'short_name' => 'education'],
                ],
                'deductions' => [
                    ['name' => 'Pension',         'short_name' => 'pension',        'type' => 'percentage', 'value' => 8,   'base' => ['basic_salary', 'housing', 'clothing'], 'combine_mode' => 'stack'],
                    ['name' => 'NHF',             'short_name' => 'nhf',            'type' => 'percentage', 'value' => 2.5, 'base' => ['basic_salary', 'housing', 'clothing'], 'combine_mode' => 'stack'],
                    ['name' => 'Life Insurance',  'short_name' => 'life_insurance', 'type' => 'amount',     'value' => 0,                                          'combine_mode' => 'stack'],
                ],
                'reliefs' => [
                    ['code' => 'CRA-1', 'name' => 'Consolidated Relief Allowance', 'type' => 'fixed',    'relief_type' => 'percentage', 'value' => 1,      'min' => 20000001, 'max' => null,     'min_status' => 'static',    'max_status' => 'unlimited', 'combine_mode' => 'stack'],
                    ['code' => 'CRA-2', 'name' => 'Consolidated Relief Allowance', 'type' => 'fixed',    'relief_type' => 'amount',     'value' => 200000, 'min' => 1,        'max' => 20000000, 'min_status' => 'static',    'max_status' => 'static',    'combine_mode' => 'stack'],
                    ['code' => 'CRA-3', 'name' => 'Consolidated Relief Allowance', 'type' => 'variable', 'relief_type' => 'percentage', 'value' => 20,     'min' => 0,        'max' => null,     'min_status' => 'unlimited', 'max_status' => 'unlimited', 'combine_mode' => 'stack'],
                ],
                'tariffs' => [
                    ['min' => 0,       'max' => 300000,  'rate_type' => 'percentage', 'rate_value' => 7,  'ordering' => 1],
                    ['min' => 300000,  'max' => 600000,  'rate_type' => 'percentage', 'rate_value' => 11, 'ordering' => 2],
                    ['min' => 600000,  'max' => 1100000, 'rate_type' => 'percentage', 'rate_value' => 15, 'ordering' => 3],
                    ['min' => 1100000, 'max' => 2700000, 'rate_type' => 'percentage', 'rate_value' => 19, 'ordering' => 4],
                    ['min' => 2700000, 'max' => 4300000, 'rate_type' => 'percentage', 'rate_value' => 21, 'ordering' => 5],
                    ['min' => 4300000, 'max' => null,    'rate_type' => 'percentage', 'rate_value' => 24, 'ordering' => 6],
                ],
                'states' => [
                    'LA' => [
                        'name' => 'Lagos State',
                        'tariffs' => [
                            ['min' => 0, 'max' => null, 'rate_type' => 'percentage', 'rate_value' => 1.5, 'ordering' => 1],
                        ],
                        'deductions' => [
                            ['short_name' => 'nhf', 'type' => 'percentage', 'value' => 2.0, 'base' => ['basic_salary', 'housing', 'clothing'], 'combine_mode' => 'override'],
                        ],
                        'locals' => [
                            'IKEJA' => [
                                'name' => 'Ikeja LGA',
                                'tariffs' => [
                                    ['min' => 0, 'max' => null, 'rate_type' => 'percentage', 'rate_value' => 0.5, 'ordering' => 1],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($data as $countryCode => $cfg) {
            // COUNTRY
            $country = TaxJurisdiction::updateOrCreate(
                ['level' => 'country', 'country_code' => strtoupper($countryCode), 'state_code' => null, 'local_code' => null],
                ['name' => $countryCode, 'currency_code' => $cfg['currency'] ?? 'NGN', 'parent_id' => null]
            );
            $vCountry = TaxVersion::updateOrCreate(
                ['tax_jurisdiction_id' => $country->id, 'tax_year' => $cfg['year']],
                ['effective_from' => "{$cfg['year']}-01-01", 'effective_to' => null]
            );

            // classes (global)
            foreach ($cfg['classes'] as $c) {
                TaxClass::updateOrCreate(['short_name' => $c['short_name']], ['name' => $c['name']]);
            }
            $classIdByShort = TaxClass::pluck('id', 'short_name');

            // link classes to country version
            foreach ($classIdByShort as $short => $cid) {
                TaxClassLink::updateOrCreate(
                    ['tax_version_id' => $vCountry->id, 'tax_class_id' => $cid],
                    ['require_deduction' => in_array($short, ['housing', 'clothing'])]
                );
            }

            // deductions (country)
            foreach ($cfg['deductions'] as $d) {
                $dc = TaxDeductionClass::updateOrCreate(['short_name' => $d['short_name']], ['name' => $d['name'] ?? $d['short_name']]);
                $rule = TaxDeductionRule::updateOrCreate(
                    ['tax_version_id' => $vCountry->id, 'tax_deduction_class_id' => $dc->id],
                    ['deduction_type' => $d['type'], 'value' => $d['value'], 'combine_mode' => $d['combine_mode'] ?? 'stack']
                );
                $baseIds = collect($d['base'] ?? [])->map(fn($s) => $classIdByShort[$s] ?? null)->filter()->values()->all();
                $rule->baseClasses()->sync($baseIds);
            }

            // reliefs (country)
            foreach ($cfg['reliefs'] as $r) {
                $rc = TaxReliefClass::updateOrCreate(['code' => $r['code']], ['name' => $r['name'], 'type' => $r['type']]);
                TaxReliefRule::updateOrCreate(
                    ['tax_version_id' => $vCountry->id, 'tax_relief_class_id' => $rc->id],
                    [
                        'relief_type' => $r['relief_type'],
                        'value' => $r['value'],
                        'minimum_amount' => $r['min'] ?? 0,
                        'maximum_amount' => $r['max'] ?? null,
                        'minimum_status' => $r['min_status'],
                        'maximum_status' => $r['max_status'],
                        'combine_mode' => $r['combine_mode'] ?? 'stack'
                    ]
                );
            }

            // tariffs (country)
            foreach ($cfg['tariffs'] as $t) {
                TaxTariff::updateOrCreate(
                    ['tax_version_id' => $vCountry->id, 'ordering' => $t['ordering']],
                    ['bracket_min' => $t['min'], 'bracket_max' => $t['max'], 'rate_type' => $t['rate_type'], 'rate_value' => $t['rate_value']]
                );
            }

            // STATES
            foreach (($cfg['states'] ?? []) as $stateCode => $s) {
                $state = TaxJurisdiction::updateOrCreate(
                    ['level' => 'state', 'country_code' => strtoupper($countryCode), 'state_code' => strtoupper($stateCode), 'local_code' => null],
                    ['name' => $s['name'], 'currency_code' => $cfg['currency'] ?? 'NGN', 'parent_id' => $country->id]
                );
                $vState = TaxVersion::updateOrCreate(
                    ['tax_jurisdiction_id' => $state->id, 'tax_year' => $cfg['year']],
                    ['effective_from' => "{$cfg['year']}-01-01", 'effective_to' => null]
                );

                foreach (($s['deductions'] ?? []) as $d) {
                    $dc = TaxDeductionClass::where('short_name', $d['short_name'])->firstOrFail();
                    $rule = TaxDeductionRule::updateOrCreate(
                        ['tax_version_id' => $vState->id, 'tax_deduction_class_id' => $dc->id],
                        ['deduction_type' => $d['type'], 'value' => $d['value'], 'combine_mode' => $d['combine_mode'] ?? 'stack']
                    );
                    $baseIds = collect($d['base'] ?? [])->map(fn($bs) => $classIdByShort[$bs] ?? null)->filter()->values()->all();
                    $rule->baseClasses()->sync($baseIds);
                }

                foreach (($s['tariffs'] ?? []) as $t) {
                    TaxTariff::updateOrCreate(
                        ['tax_version_id' => $vState->id, 'ordering' => $t['ordering']],
                        ['bracket_min' => $t['min'], 'bracket_max' => $t['max'], 'rate_type' => $t['rate_type'], 'rate_value' => $t['rate_value']]
                    );
                }

                foreach (($s['locals'] ?? []) as $localCode => $l) {
                    $local = TaxJurisdiction::updateOrCreate(
                        ['level' => 'local', 'country_code' => strtoupper($countryCode), 'state_code' => strtoupper($stateCode), 'local_code' => strtoupper($localCode)],
                        ['name' => $l['name'], 'currency_code' => $cfg['currency'] ?? 'NGN', 'parent_id' => $state->id]
                    );
                    $vLocal = TaxVersion::updateOrCreate(
                        ['tax_jurisdiction_id' => $local->id, 'tax_year' => $cfg['year']],
                        ['effective_from' => "{$cfg['year']}-01-01", 'effective_to' => null]
                    );

                    foreach (($l['tariffs'] ?? []) as $t) {
                        TaxTariff::updateOrCreate(
                            ['tax_version_id' => $vLocal->id, 'ordering' => $t['ordering']],
                            ['bracket_min' => $t['min'], 'bracket_max' => $t['max'], 'rate_type' => $t['rate_type'], 'rate_value' => $t['rate_value']]
                        );
                    }
                }
            }
        }
    }
}
