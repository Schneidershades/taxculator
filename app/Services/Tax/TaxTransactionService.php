<?php

namespace App\Services\Tax;

use App\Models\TaxClass;
use App\Models\TaxVersion;
use App\Models\TaxReliefRule;
use App\Support\TaxRuleCache;
use App\Models\TaxTransaction;
use App\Models\TaxJurisdiction;
use App\Models\ContributionRule;
use App\Models\TaxDeductionRule;
use App\Models\WithholdingCredit;
use Illuminate\Support\Collection;
use App\Services\Fx\FxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Tax\StatementBuilder;
use App\Services\Tax\RuleSnapshotBuilder;

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
                ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)
                    ->where('tax_year', $p['tax_year'])
                    ->active()
                    ->first())
                ->filter()
                ->values();

            if ($versions->isEmpty()) {
                throw new \RuntimeException('No tax versions found for the given year/jurisdiction');
            }

            $bundles = $versions->map(fn($v) => TaxRuleCache::pitBundle($v->id))->all();

            $userId = $p['user_id'] ?? Auth::id();

            $tx = TaxTransaction::create([
                'user_id'         => $userId,
                'idempotency_key' => $p['idempotency_key'] ?? null,
            ]);

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

            if (!empty($p['idempotency_key'])) {
                $tx->update(['idempotency_key' => $p['idempotency_key']]);
            }

            // 5) Tariffs per version level (country/state/local)
            $totalTax = 0;
            foreach ($bundles as $b) {
                $component = $this->applyTariffsArray($b['tariffs'], $taxable, $tx);
                if ($component > 0) {
                    $label = match ($b['level']) {
                        'country' => 'countryTax',
                        'state'   => 'stateTax',
                        'local'   => 'localTax',
                    };
                    $totalTax += $component;
                    $this->rel($tx, $label, $component, 'amount', $b['version_id'], TaxVersion::class);
                }
            }

            $this->rel($tx, 'totalTax', $totalTax, 'amount');

            $creditsApplied = 0.0;
            if (!empty($p['beneficiary_id'])) {
                [$creditsApplied, $creditDetails] = $this->applyWithholdingCredits(
                    (int)$p['beneficiary_id'],
                    (int)$p['tax_year'],
                    (float)$totalTax
                );

                // One relation per credit used (traceability)
                foreach ($creditDetails as $cd) {
                    $this->rel($tx, 'withholdingCreditApplied', $cd['used'], 'amount', $cd['credit_id'], \App\Models\WithholdingCredit::class);
                }
            }

            $mergedContribs = $this->mergeContributionsFromBundles($bundles);

            $employeeTotal = 0.0;
            $employerTotal = 0.0;

            foreach ($mergedContribs as $rc) {
                [$emp, $er, $appliedBy, $base, $name] = $this->evalContributionFromBundle($rc, $incomeMap);
                if ($emp > 0) {
                    $employeeTotal += $emp;
                    $this->rel($tx, 'employeeContribution', $emp, $appliedBy, null, null);
                }
                if ($er > 0) {
                    $employerTotal += $er;
                    $this->rel($tx, 'employerContribution', $er, $appliedBy, null, null);
                }
            }
            $this->rel($tx, 'employeeContributionTotal', round($employeeTotal, 2), 'amount');
            $this->rel($tx, 'employerContributionTotal', round($employerTotal, 2), 'amount');

            // Net tax due after credits
            $netTax = max(0, round((float)$totalTax - (float)$creditsApplied, 2));
            $this->rel($tx, 'netTaxDue', $netTax, 'amount');

            $inputs = [
                'country_code' => $p['country_code'] ?? null,
                'state_code'   => $p['state_code']   ?? null,
                'local_code'   => $p['local_code']   ?? null,
                'tax_year'     => $p['tax_year'],
                'classes'      => $p['classes']      ?? [],
                'deductions'   => $p['deductions']   ?? [],
            ];

            // 2) Versions snapshot (rules) and hash
            $versionsSnapshot = RuleSnapshotBuilder::build($versions->all());
            $rulesHash        = RuleSnapshotBuilder::hash($versionsSnapshot);

            // 3) Full statement
            $statement = StatementBuilder::from($tx->load('relations'), $inputs, $versionsSnapshot);

            $baseCurrency    = $country->base_currency ?? 'NGN';
            $displayCurrency = isset($p['currency_code']) ? strtoupper((string) $p['currency_code']) : null;

            $currencies = [
                'base_currency'    => $baseCurrency,
                'display_currency' => null,
                'fx'               => null,
            ];

            if ($displayCurrency && $displayCurrency !== $baseCurrency) {
                /** @var \App\Services\Fx\FxService $fxSvc */
                $fxSvc = app(\App\Services\Fx\FxService::class);
                $fx    = $fxSvc->resolve($baseCurrency, $displayCurrency, now()->toDateString());

                $currencies['display_currency'] = $displayCurrency;
                $currencies['fx']               = $fx;

                $precision = (int) config('fx.precision', 2);

                // Convert normalized statement amounts
                $statement['amounts_display'] = $fxSvc->convertArray(
                    $statement['amounts'],
                    (float) $fx['rate'],
                    $precision
                );
            }

            // ✅ Put currencies inside meta (what the tests read) and also top-level for convenience
            $statement['meta']['currencies'] = $currencies;
            $statement['currencies']         = $currencies;

            // Persist snapshots (+ statement)
            $tx->update([
                'input_snapshot'    => $inputs,
                'versions_snapshot' => $versionsSnapshot,
                'rules_hash'        => $rulesHash,
                'statement'         => $statement,
            ]);

            return $tx->fresh('relations');
        });
    }

    /**
     * Stateless preview: compute breakdown without saving anything.
     * Returns an array matching the TaxTransactionResource "amounts/breakdown" shape.
     */
    public function preview(array $p): array
    {
        // ---- resolve jurisdictions & active versions ----
        $country = TaxJurisdiction::country($p['country_code'])->firstOrFail();
        $state   = !empty($p['state_code'])
            ? TaxJurisdiction::state($p['country_code'], $p['state_code'])->first()
            : null;
        $local   = (!empty($p['local_code']) && $state)
            ? TaxJurisdiction::local($p['country_code'], $p['state_code'], $p['local_code'])->first()
            : null;

        $versions = collect([$country, $state, $local])->filter()
            ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)
                ->where('tax_year', $p['tax_year'])
                ->active()
                ->first())
            ->filter()
            ->values();

        if ($versions->isEmpty()) {
            throw new \RuntimeException('No tax versions found for the given year/jurisdiction');
        }

        // ---- pull cached rule bundles for each version (country/state/local) ----
        $bundles = $versions->map(fn($v) => TaxRuleCache::pitBundle($v->id))->all();

        // ---- inputs & class map ----
        $classMap  = TaxClass::query()->get()->keyBy('short_name');
        $incomeMap = collect($p['classes'] ?? [])->map(fn($v) => (float) $v)->all();

        // ---- breakdown shells ----
        $breakdown = [
            'classes'                 => [],
            'deductions'              => [],
            'reliefs'                 => [],
            'tariffs'                 => [],
            'employee_contributions'  => [],
            'employer_contributions'  => [],
        ];

        // ---- classes & gross ----
        $gross = 0.0;
        foreach ($incomeMap as $short => $amt) {
            if ($amt <= 0 || !isset($classMap[$short])) {
                continue;
            }
            $gross += $amt;
            $breakdown['classes'][] = [
                'description' => 'taxClass',
                'applied_by'  => 'amount',
                'value'       => round($amt, 2),
                'related'     => [
                    'type'       => 'TaxClass',
                    'id'         => $classMap[$short]->id,
                    'short_name' => $short,
                ],
            ];
        }

        // ---- deductions (merge across bundles with combine_mode) ----
        $flags = $p['deductions'] ?? [];
        $mergedDeductions = $this->mergeDeductionsFromBundles($bundles, $flags);

        $deductTotal = 0.0;
        foreach ($mergedDeductions as $mr) {
            [$val, $appliedBy] = $this->evalDeduction($mr['rule'], $incomeMap);
            if ($val > 0) {
                $deductTotal += $val;
                $breakdown['deductions'][] = [
                    'description' => 'deduction',
                    'applied_by'  => $appliedBy,
                    'value'       => round($val, 2),
                    'related'     => [
                        'type'       => 'TaxDeductionRule',
                        'id'         => $mr['rule']->id,
                        'short_name' => $mr['rule']->deductionClass->short_name,
                    ],
                ];
            }
        }

        // ---- reliefs (merge across bundles with combine_mode) ----
        $mergedReliefs = $this->mergeReliefsFromBundles($bundles);
        $reliefTotal   = 0.0;

        foreach ($mergedReliefs as $mr) {
            $val = $this->evalRelief($mr['rule'], $gross - $deductTotal);
            if ($val > 0) {
                $reliefTotal += $val;
                $breakdown['reliefs'][] = [
                    'description' => 'relief',
                    'applied_by'  => $mr['rule']->relief_type,
                    'value'       => round($val, 2),
                    'related'     => [
                        'type' => 'TaxReliefRule',
                        'id'   => $mr['rule']->id,
                        'code' => $mr['rule']->reliefClass->code,
                    ],
                ];
            }
        }

        // ---- taxable income ----
        $taxable = max(0, $gross - $deductTotal - $reliefTotal);

        // ---- tariffs per bundle (country/state/local) ----
        $countryTax = 0.0;
        $stateTax   = 0.0;
        $localTax   = 0.0;

        foreach ($bundles as $b) {
            $remaining = $taxable;
            $component = 0.0;

            foreach ($b['tariffs'] as $t) {
                if ($remaining <= 0) break;

                $min  = (float) $t['min'];
                $max  = is_null($t['max']) ? INF : (float) $t['max'];
                $span = max(0, min($remaining, $max - $min));
                if ($span <= 0) continue;

                $chunk = $t['rate_type'] === 'percentage'
                    ? round($span * ((float) $t['rate_value'] / 100), 2)
                    : round((float) $t['rate_value'], 2);

                $component += $chunk;
                $breakdown['tariffs'][] = [
                    'description' => 'taxedIncomeByTariff',
                    'applied_by'  => $t['rate_type'],
                    'value'       => $chunk,
                    'related'     => [
                        'type'  => 'TaxTariff',
                        'id'    => $t['id'] ?? null,
                        'level' => $b['level'],   // country|state|local
                        'min'   => (float) $t['min'],
                        'max'   => $t['max'],
                        'rate'  => (float) $t['rate_value'],
                    ],
                ];

                $remaining -= $span;
            }

            if ($b['level'] === 'country') $countryTax += $component;
            if ($b['level'] === 'state')   $stateTax   += $component;
            if ($b['level'] === 'local')   $localTax   += $component;
        }

        // ---- optional: contributions (read-only in preview) ----
        $employeeTotal = 0.0;
        $employerTotal = 0.0;

        $mergedContribs = $this->mergeContributionsFromBundles($bundles);
        foreach ($mergedContribs as $rc) {
            [$emp, $er, $appliedBy, $base, $name] = $this->evalContributionFromBundle($rc, $incomeMap);
            if ($emp > 0) {
                $employeeTotal += $emp;
                $breakdown['employee_contributions'][] = [
                    'description' => 'employeeContribution',
                    'applied_by'  => $appliedBy,
                    'value'       => $emp,
                    'related'     => ['name' => $name, 'base' => $base],
                ];
            }
            if ($er > 0) {
                $employerTotal += $er;
                $breakdown['employer_contributions'][] = [
                    'description' => 'employerContribution',
                    'applied_by'  => $appliedBy,
                    'value'       => $er,
                    'related'     => ['name' => $name, 'base' => $base],
                ];
            }
        }

        // ---- base amounts (jurisdiction currency) ----
        $amounts = [
            'gross_income'      => round($gross, 2),
            'taxable_income'    => round($taxable, 2),
            'country_tax'       => round($countryTax, 2),
            'state_tax'         => round($stateTax, 2),
            'local_tax'         => round($localTax, 2),
            'total_tax'         => round($countryTax + $stateTax + $localTax, 2),
            'employee_contrib'  => round($employeeTotal, 2),
            'employer_contrib'  => round($employerTotal, 2),
        ];

        // ---- FX presentation (optional): amounts_display + currencies ----
        $baseCurrency    = $country->base_currency ?? 'NGN';
        $displayCurrency = !empty($p['currency_code']) ? strtoupper($p['currency_code']) : null;

        // default currencies block if no FX requested
        $result = [
            'amounts'    => $amounts,
            'breakdown'  => $breakdown,
            'currencies' => [
                'base_currency'    => $baseCurrency,
                'display_currency' => null,
                'fx'               => null,
            ],
        ];

        if ($displayCurrency && $displayCurrency !== $baseCurrency) {
            /** @var FxService $fxSvc */
            $fxSvc = app(FxService::class);

            // resolve pair & rate (e.g., NGN/USD)
            $fx = $fxSvc->resolve($baseCurrency, $displayCurrency, now()->toDateString());

            // convert amounts to display currency
            $precision      = (int) config('fx.precision', 2);
            $amountsDisplay = $fxSvc->convertArray($amounts, (float) $fx['rate'], $precision);

            $result['amounts_display'] = $amountsDisplay;
            $result['currencies'] = [
                'base_currency'    => $baseCurrency,
                'display_currency' => $displayCurrency,
                'fx'               => $fx, // ['pair' => 'NGN/USD', 'rate' => 0.0012, 'as_of' => 'YYYY-MM-DD', ...]
            ];
        }

        return $result;
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

    private function evalDeduction($r, array $incomeMap): array
    {
        // type / value
        $type  = $r->deduction_type ?? $r->type ?? 'percentage';
        $value = (float) ($r->value ?? 0);

        if ($type === 'amount') {
            return [round($value, 2), 'amount'];
        }

        // base shorts (Eloquent relation OR bundle collection/array)
        if (method_exists($r, 'baseClasses')) {
            $baseShorts = $r->baseClasses()->pluck('short_name')->all();
        } elseif (isset($r->baseClasses) && $r->baseClasses instanceof Collection) {
            $baseShorts = $r->baseClasses->all();
        } elseif (isset($r->base_shorts) && is_array($r->base_shorts)) {
            $baseShorts = $r->base_shorts;
        } else {
            $baseShorts = [];
        }

        $base = (float) collect($incomeMap)->only($baseShorts)->sum();

        return [round($base * ($value / 100), 2), 'percentage'];
    }

    private function evalRelief($r, float $base): float
    {
        $withinMin = $r->minimum_status === 'unlimited' || $base >= (float) $r->minimum_amount;
        $withinMax = $r->maximum_status === 'unlimited' || $base <= (float) $r->maximum_amount;
        if (!($withinMin && $withinMax)) return 0.0;

        return $r->relief_type === 'percentage'
            ? round($base * ((float) $r->value / 100), 2)
            : round((float) $r->value, 2);
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

    /**
     * Applies available WHT credits FIFO and returns [appliedAmount, details[]].
     * Locks rows for safe concurrent consumption.
     */
    private function applyWithholdingCredits(int $beneficiaryId, int $taxYear, float $liability): array
    {
        $remaining = $liability;
        $applied   = 0.0;
        $details   = [];

        // Lock credits so two requests don't consume the same credit simultaneously
        $credits = WithholdingCredit::where('beneficiary_id', $beneficiaryId)
            ->where('tax_year', $taxYear)
            ->where('remaining_amount', '>', 0)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($credits as $c) {
            if ($remaining <= 0) break;

            $use = min((float)$c->remaining_amount, $remaining);
            if ($use <= 0) continue;

            $c->remaining_amount = round((float)$c->remaining_amount - $use, 2);
            if ($c->remaining_amount <= 0 && is_null($c->consumed_at)) {
                $c->consumed_at = now();
            }
            $c->save();

            $applied   += $use;
            $remaining -= $use;
            $details[]  = ['credit_id' => $c->id, 'used' => $use];
        }

        return [round($applied, 2), $details];
    }

    /** Merge contribution rules across versions with combine_mode semantics. */
    private function mergeContributionRules(Collection $versions): array
    {
        $picked = [];
        foreach ($versions as $v) {
            foreach ($v->contributionRules()->get() as $r) {
                $key = $r->name; // merge by logical name (e.g. "Pension")
                if (!isset($picked[$key])) {
                    $picked[$key] = ['rule' => $r];
                } else {
                    if ($r->combine_mode === 'override') {
                        $picked[$key] = ['rule' => $r];
                    } elseif ($picked[$key]['rule']->combine_mode === 'stack' && $r->combine_mode === 'stack') {
                        // stacking: add rates & caps sensibly (keep it simple: stack rates)
                        $picked[$key]['rule']->employee_rate = (float)$picked[$key]['rule']->employee_rate + (float)$r->employee_rate;
                        $picked[$key]['rule']->employer_rate = (float)$picked[$key]['rule']->employer_rate + (float)$r->employer_rate;
                    } else {
                        $picked[$key] = ['rule' => $r];
                    }
                }
            }
        }
        return array_values($picked);
    }

    /** Calculate employee & employer contribution for a rule. */
    private function evalContribution(ContributionRule $r, array $incomeMap): array
    {
        $base = 0.0;
        if ($r->base_type === 'gross') {
            $base = (float) collect($incomeMap)->sum();
        } else {
            $shorts = $r->baseClasses()->pluck('short_name')->all();
            $base   = (float) collect($incomeMap)->only($shorts)->sum();
        }

        $appliedBy = $r->rate_type;

        // compute amounts
        if ($r->rate_type === 'amount') {
            $emp = (float) ($r->employee_rate ?? 0);
            $er  = (float) ($r->employer_rate ?? 0);
        } else { // percentage
            $emp = (float) $base * ((float) ($r->employee_rate ?? 0) / 100);
            $er  = (float) $base * ((float) ($r->employer_rate ?? 0) / 100);
        }

        // floors
        if (!is_null($r->employee_floor)) $emp = max($emp, (float) $r->employee_floor);
        if (!is_null($r->employer_floor)) $er  = max($er,  (float) $r->employer_floor);

        // caps
        if (!is_null($r->employee_cap)) $emp = min($emp, (float) $r->employee_cap);
        if (!is_null($r->employer_cap)) $er  = min($er,  (float) $r->employer_cap);

        return [round($emp, 2), round($er, 2), $appliedBy, $base];
    }

    private function mergeDeductionsFromBundles($bundles, array $flags): array
    {
        $picked = [];
        foreach ($bundles as $b) {
            foreach ($b['deductions'] as $r) {
                $short = $r['short'];
                if (!($flags[$short] ?? false)) continue;
                $key = $short; // per logical deduction type
                if (!isset($picked[$key])) {
                    $picked[$key] = $r;
                } else {
                    if (($r['combine_mode'] ?? 'stack') === 'override') {
                        $picked[$key] = $r;
                    } elseif (($picked[$key]['combine_mode'] ?? 'stack') === 'stack' && ($r['combine_mode'] ?? 'stack') === 'stack') {
                        $picked[$key]['value'] += $r['value'];
                    } else {
                        $picked[$key] = $r;
                    }
                }
            }
        }
        // unify to objects your existing eval uses
        return array_map(function ($r) {
            return ['rule' => (object)[
                'id' => $r['id'],
                'deduction_type' => $r['type'],
                'value' => $r['value'],
                'combine_mode' => $r['combine_mode'],
                'baseClasses' => collect($r['base_shorts']),
                'deductionClass' => (object)['short_name' => $r['short']],
            ]];
        }, array_values($picked));
    }

    private function mergeReliefsFromBundles($bundles): array
    {
        $picked = [];
        foreach ($bundles as $b) {
            foreach ($b['reliefs'] as $r) {
                $key = $r['code'] ?? $r['id'];
                if (!isset($picked[$key])) {
                    $picked[$key] = $r;
                } else {
                    if (($r['combine_mode'] ?? 'stack') === 'override') {
                        $picked[$key] = $r;
                    } elseif (($picked[$key]['combine_mode'] ?? 'stack') === 'stack' && ($r['combine_mode'] ?? 'stack') === 'stack') {
                        $picked[$key]['value'] += $r['value'];
                    } else {
                        $picked[$key] = $r;
                    }
                }
            }
        }

        return array_map(function ($r) {
            return ['rule' => (object)[
                'id' => $r['id'],
                'relief_type' => $r['relief_type'],
                'value' => $r['value'],
                'minimum_amount' => $r['minimum_amount'],
                'maximum_amount' => $r['maximum_amount'],
                'minimum_status' => $r['minimum_status'],
                'maximum_status' => $r['maximum_status'],
                'combine_mode' => $r['combine_mode'],
                'reliefClass' => (object)['code' => $r['code']],
            ]];
        }, array_values($picked));
    }

    private function applyTariffsArray(array $tariffs, float $taxable, TaxTransaction $tx): float
    {
        $remaining = $taxable;
        $total = 0.0;
        foreach ($tariffs as $b) {
            if ($remaining <= 0) break;
            $min = (float)$b['min'];
            $max = is_null($b['max']) ? INF : (float)$b['max'];
            $span = max(0, min($remaining, $max - $min));
            if ($span <= 0) continue;

            $chunk = $b['rate_type'] === 'percentage'
                ? round($span * ($b['rate_value'] / 100), 2)
                : round($b['rate_value'], 2);

            $total += $chunk;
            $this->rel($tx, 'taxedIncomeByTariff', $chunk, $b['rate_type']);
            $remaining -= $span;
        }
        return $total;
    }

    private function mergeContributionsFromBundles(array $bundles): array
    {
        $picked = [];
        foreach ($bundles as $b) {
            foreach ($b['contributions'] as $r) {
                $key = $r['name'];
                if (!isset($picked[$key])) {
                    $picked[$key] = $r;
                } else {
                    if (($r['combine_mode'] ?? 'stack') === 'override') {
                        $picked[$key] = $r;
                    } elseif (($picked[$key]['combine_mode'] ?? 'stack') === 'stack' && ($r['combine_mode'] ?? 'stack') === 'stack') {
                        $picked[$key]['employee_rate'] = (float)($picked[$key]['employee_rate'] ?? 0) + (float)($r['employee_rate'] ?? 0);
                        $picked[$key]['employer_rate'] = (float)($picked[$key]['employer_rate'] ?? 0) + (float)($r['employer_rate'] ?? 0);
                    } else {
                        $picked[$key] = $r;
                    }
                }
            }
        }
        return array_values($picked);
    }

    private function evalContributionFromBundle(array $r, array $incomeMap): array
    {
        $base = 0.0;
        if (($r['base_type'] ?? 'gross') === 'gross') {
            $base = (float) collect($incomeMap)->sum();
        } else {
            $base = (float) collect($incomeMap)->only($r['base_shorts'] ?? [])->sum();
        }

        $appliedBy = $r['rate_type'] ?? 'percentage';

        if ($appliedBy === 'amount') {
            $emp = (float) ($r['employee_rate'] ?? 0);
            $er  = (float) ($r['employer_rate'] ?? 0);
        } else {
            $emp = $base * ((float)($r['employee_rate'] ?? 0) / 100);
            $er  = $base * ((float)($r['employer_rate'] ?? 0) / 100);
        }

        // floors/caps
        if (isset($r['employee_floor'])) $emp = max($emp, (float)$r['employee_floor']);
        if (isset($r['employer_floor'])) $er  = max($er,  (float)$r['employer_floor']);

        if (isset($r['employee_cap'])) $emp = min($emp, (float)$r['employee_cap']);
        if (isset($r['employer_cap'])) $er  = min($er,  (float)$r['employer_cap']);

        return [round($emp, 2), round($er, 2), $appliedBy, $base, $r['name']];
    }
}
