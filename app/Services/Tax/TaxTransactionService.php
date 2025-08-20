<?php

namespace App\Services\Tax;

use App\Models\TaxClass;
use App\Models\TaxVersion;
use App\Models\TaxReliefRule;
use App\Models\TaxTransaction;
use App\Models\TaxJurisdiction;
use App\Models\TaxDeductionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaxTransactionService
{
    /**
     * Persist a full tax transaction and return it with relations.
     */
    public function register(array $p): TaxTransaction
    {
        return DB::transaction(function () use ($p) {
            $country = TaxJurisdiction::country($p['country_code'])->firstOrFail();
            $state   = !empty($p['state_code']) ? TaxJurisdiction::state($p['country_code'], $p['state_code'])->first() : null;
            $local   = (!empty($p['local_code']) && $state) ? TaxJurisdiction::local($p['country_code'], $p['state_code'], $p['local_code'])->first() : null;

            $versions = collect([$country, $state, $local])->filter()
                ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)->where('tax_year', $p['tax_year'])->first())
                ->filter()
                ->values();

            if ($versions->isEmpty()) {
                throw new \RuntimeException('No tax versions found for the given year/jurisdiction');
            }

            $tx = TaxTransaction::create([]);

            // Preload classes & income map
            $classMap = TaxClass::query()->get()->keyBy('short_name');
            $incomeMap = collect($p['classes'])->map(fn($v) => (float)$v)->all();

            // 1) Gross income & relations
            $gross = 0;
            foreach ($incomeMap as $short => $amt) {
                if ($amt <= 0) continue;
                if (!isset($classMap[$short])) continue;
                $gross += $amt;
                $this->rel($tx, 'taxClass', $amt, 'amount', $classMap[$short]->id, TaxClass::class);
            }
            $this->rel($tx, 'grossIncome', $gross, 'amount');

            // 2) Merge deduction rules across versions
            $flags = $p['deductions'] ?? [];
            $mergedDeductions = $this->mergeDeductions($versions, $flags);

            $deductTotal = 0;
            foreach ($mergedDeductions as $mr) {
                [$val, $appliedBy] = $this->evalDeduction($mr['rule'], $incomeMap);
                if ($val > 0) {
                    $deductTotal += $val;
                    $this->rel($tx, 'deduction', $val, $appliedBy, $mr['rule']->id, TaxDeductionRule::class);
                }
            }

            // 3) Merge reliefs across versions
            $mergedReliefs = $this->mergeReliefs($versions);
            $reliefTotal = 0;
            foreach ($mergedReliefs as $mr) {
                $val = $this->evalRelief($mr['rule'], $gross - $deductTotal);
                if ($val > 0) {
                    $reliefTotal += $val;
                    $this->rel($tx, 'relief', $val, $mr['rule']->relief_type, $mr['rule']->id, TaxReliefRule::class);
                }
            }

            // 4) Taxable income
            $taxable = max(0, $gross - $deductTotal - $reliefTotal);
            $this->rel($tx, 'taxableIncome', $taxable, 'amount');

            // 5) Tariffs per version level (country/state/local)
            $totalTax = 0;
            foreach ($versions as $v) {
                $component = $this->applyTariffs($v->tariffs()->orderBy('ordering')->get(), $taxable, $tx);
                if ($component > 0) {
                    $label = match ($v->jurisdiction->level) {
                        'country' => 'countryTax',
                        'state'   => 'stateTax',
                        'local'   => 'localTax',
                    };
                    $totalTax += $component;
                    $this->rel($tx, $label, $component, 'amount', $v->id, TaxVersion::class);
                }
            }

            $this->rel($tx, 'totalTax', $totalTax, 'amount');

            return $tx->fresh('relations');
        });
    }

    /**
     * Stateless preview: compute breakdown without saving anything.
     * Returns an array matching the TaxTransactionResource "amounts/breakdown" shape.
     */
    public function preview(array $p): array
    {
        $country = TaxJurisdiction::country($p['country_code'])->firstOrFail();
        $state   = !empty($p['state_code']) ? TaxJurisdiction::state($p['country_code'], $p['state_code'])->first() : null;
        $local   = (!empty($p['local_code']) && $state) ? TaxJurisdiction::local($p['country_code'], $p['state_code'], $p['local_code'])->first() : null;

        $versions = collect([$country, $state, $local])->filter()
            ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)->where('tax_year', $p['tax_year'])->first())
            ->filter()
            ->values();

        if ($versions->isEmpty()) {
            throw new \RuntimeException('No tax versions found for the given year/jurisdiction');
        }

        $classMap  = TaxClass::query()->get()->keyBy('short_name');
        $incomeMap = collect($p['classes'])->map(fn($v) => (float)$v)->all();

        $breakdown = [
            'classes'    => [],
            'deductions' => [],
            'reliefs'    => [],
            'tariffs'    => [], // country/state/local bracket lines
        ];

        // classes & gross
        $gross = 0;
        foreach ($incomeMap as $short => $amt) {
            if ($amt <= 0 || !isset($classMap[$short])) continue;
            $gross += $amt;
            $breakdown['classes'][] = [
                'description' => 'taxClass',
                'applied_by'  => 'amount',
                'value'       => round($amt, 2),
                'related'     => ['type' => 'TaxClass', 'id' => $classMap[$short]->id, 'short_name' => $short],
            ];
        }

        // deductions
        $flags = $p['deductions'] ?? [];
        $mergedDeductions = $this->mergeDeductions($versions, $flags);

        $deductTotal = 0;
        foreach ($mergedDeductions as $mr) {
            [$val, $appliedBy] = $this->evalDeduction($mr['rule'], $incomeMap);
            if ($val > 0) {
                $deductTotal += $val;
                $breakdown['deductions'][] = [
                    'description' => 'deduction',
                    'applied_by'  => $appliedBy,
                    'value'       => round($val, 2),
                    'related'     => ['type' => 'TaxDeductionRule', 'id' => $mr['rule']->id, 'short_name' => $mr['rule']->deductionClass->short_name],
                ];
            }
        }

        // reliefs
        $mergedReliefs = $this->mergeReliefs($versions);
        $reliefTotal = 0;
        foreach ($mergedReliefs as $mr) {
            $val = $this->evalRelief($mr['rule'], $gross - $deductTotal);
            if ($val > 0) {
                $reliefTotal += $val;
                $breakdown['reliefs'][] = [
                    'description' => 'relief',
                    'applied_by'  => $mr['rule']->relief_type,
                    'value'       => round($val, 2),
                    'related'     => ['type' => 'TaxReliefRule', 'id' => $mr['rule']->id, 'code' => $mr['rule']->reliefClass->code],
                ];
            }
        }

        $taxable = max(0, $gross - $deductTotal - $reliefTotal);

        // tariffs per level
        $countryTax = $stateTax = $localTax = 0.0;
        foreach ($versions as $v) {
            $component = 0.0;
            $remainingBase = $taxable;

            foreach ($v->tariffs()->orderBy('ordering')->get() as $b) {
                if ($remainingBase <= 0) break;
                $min = (float)$b->bracket_min;
                $max = is_null($b->bracket_max) ? INF : (float)$b->bracket_max;
                $span = max(0, min($remainingBase, $max - $min));
                if ($span <= 0) continue;

                $chunk = $b->rate_type === 'percentage'
                    ? round($span * ((float)$b->rate_value / 100), 2)
                    : round((float)$b->rate_value, 2);

                $component += $chunk;
                $breakdown['tariffs'][] = [
                    'description' => 'taxedIncomeByTariff',
                    'applied_by'  => $b->rate_type,
                    'value'       => $chunk,
                    'related'     => [
                        'type' => 'TaxTariff',
                        'id' => $b->id,
                        'level' => $v->jurisdiction->level,
                        'min' => (float)$b->bracket_min,
                        'max' => $b->bracket_max,
                        'rate' => (float)$b->rate_value,
                    ],
                ];

                $remainingBase -= $span;
            }

            $level = $v->jurisdiction->level;
            if ($level === 'country') $countryTax += $component;
            if ($level === 'state')   $stateTax   += $component;
            if ($level === 'local')   $localTax   += $component;
        }

        $amounts = [
            'gross_income'   => round($gross, 2),
            'taxable_income' => round($taxable, 2),
            'country_tax'    => round($countryTax, 2),
            'state_tax'      => round($stateTax, 2),
            'local_tax'      => round($localTax, 2),
            'total_tax'      => round($countryTax + $stateTax + $localTax, 2),
        ];

        return [
            'amounts'   => $amounts,
            'breakdown' => $breakdown,
        ];
    }

    // ---------- internals ----------

    private function rel(TaxTransaction $tx, string $desc, float $value, string $appliedBy, $modelId = null, $modelType = null)
    {
        $tx->relations()->create([
            'tax_transaction_relationable_id'   => $modelId,
            'tax_transaction_relationable_type' => $modelType,
            'description' => $desc,
            'value'       => $value,
            'applied_by'  => $appliedBy,
        ]);
    }

    private function mergeDeductions(Collection $versions, array $flags): array
    {
        $picked = [];
        foreach ($versions as $v) {
            foreach ($v->deductionRules()->with('deductionClass')->get() as $r) {
                $short = $r->deductionClass->short_name;
                if (!($flags[$short] ?? false)) continue;

                $key = $r->tax_deduction_class_id;
                if (!isset($picked[$key])) {
                    $picked[$key] = ['rule' => $r];
                } else {
                    if ($r->combine_mode === 'override') {
                        $picked[$key] = ['rule' => $r];
                    } elseif ($picked[$key]['rule']->combine_mode === 'stack' && $r->combine_mode === 'stack') {
                        $picked[$key]['rule']->value += $r->value;
                    } else {
                        $picked[$key] = ['rule' => $r];
                    }
                }
            }
        }
        return array_values($picked);
    }

    private function mergeReliefs(Collection $versions): array
    {
        $picked = [];
        foreach ($versions as $v) {
            foreach ($v->reliefRules()->with('reliefClass')->get() as $r) {
                $key = $r->tax_relief_class_id;
                if (!isset($picked[$key])) {
                    $picked[$key] = ['rule' => $r];
                } else {
                    if ($r->combine_mode === 'override') {
                        $picked[$key] = ['rule' => $r];
                    } elseif ($picked[$key]['rule']->combine_mode === 'stack' && $r->combine_mode === 'stack') {
                        $picked[$key]['rule']->value += $r->value;
                    } else {
                        $picked[$key] = ['rule' => $r];
                    }
                }
            }
        }
        return array_values($picked);
    }

    private function evalDeduction(TaxDeductionRule $r, array $incomeMap): array
    {
        if ($r->deduction_type === 'amount') {
            return [round((float)$r->value, 2), 'amount'];
        }
        $baseShorts = $r->baseClasses()->pluck('short_name')->all();
        $base = collect($incomeMap)->only($baseShorts)->sum();
        return [round($base * ((float)$r->value / 100), 2), 'percentage'];
    }

    private function evalRelief(TaxReliefRule $r, float $base): float
    {
        $withinMin = $r->minimum_status === 'unlimited' || $base >= (float)$r->minimum_amount;
        $withinMax = $r->maximum_status === 'unlimited' || $base <= (float)$r->maximum_amount;
        if (!($withinMin && $withinMax)) return 0.0;

        return $r->relief_type === 'percentage'
            ? round($base * ((float)$r->value / 100), 2)
            : round((float)$r->value, 2);
    }

    private function applyTariffs($tariffs, float $taxable, TaxTransaction $tx): float
    {
        $remainingBase = $taxable;
        $total = 0.0;

        foreach ($tariffs as $b) {
            if ($remainingBase <= 0) break;

            $min = (float)$b->bracket_min;
            $max = is_null($b->bracket_max) ? INF : (float)$b->bracket_max;
            $span = max(0, min($remainingBase, $max - $min));
            if ($span <= 0) continue;

            $chunk = $b->rate_type === 'percentage'
                ? round($span * ((float)$b->rate_value / 100), 2)
                : round((float)$b->rate_value, 2);

            $total += $chunk;
            $this->rel($tx, 'taxedIncomeByTariff', $chunk, $b->rate_type);

            $remainingBase -= $span;
        }

        return $total;
    }
}
