<?php

namespace App\Services\Tax;

use App\Models\TaxTransaction;

class StatementBuilder
{
    public static function from(TaxTransaction $tx, array $inputs, array $ruleSnapshot): array
    {
        $rels = $tx->relations ?? collect();

        $byDesc = fn(string $d) => $rels->where('description', $d);
        $sum    = fn($set) => (float) $set->sum('value');

        $gross     = (float) optional($byDesc('grossIncome')->first())->value
            ?: (float) $byDesc('taxClass')->sum('value');
        $dedTotal  = $sum($byDesc('deduction'));
        $relTotal  = $sum($byDesc('relief'));
        $taxable   = (float) optional($byDesc('taxableIncome')->first())->value
            ?: max(0, $gross - $dedTotal - $relTotal);

        $country   = $sum($rels->where('description', 'countryTax'));
        $state     = $sum($rels->where('description', 'stateTax'));
        $local     = $sum($rels->where('description', 'localTax'));
        $totalTax  = $country + $state + $local;
        $credits   = $sum($rels->where('description', 'withholdingCreditApplied'));
        $netTax    = max(0, $totalTax - $credits);

        $baseCurrency    = data_get($tx->versions_snapshot, '0.jurisdiction.base_currency') // try snapshot first
            ?: data_get($tx->statement, 'currencies.base_currency')
            ?: 'NGN';
        $displayCurrency = $tx->display_currency;
        $fxSnapshot      = $tx->fx_snapshot; // ['pair','rate','as_of_date','source']

        $payload = [
            'meta' => [
                'transaction_id' => $tx->id,
                'identifier'     => $tx->identifier,
                'computed_at'    => optional($tx->created_at)->toISOString(),
                'version'        => 1,
            ],
            'inputs' => [
                'jurisdiction' => [
                    'country_code' => $inputs['country_code'] ?? null,
                    'state_code'   => $inputs['state_code'] ?? null,
                    'local_code'   => $inputs['local_code'] ?? null,
                    'tax_year'     => $inputs['tax_year'] ?? null,
                ],
                'classes'    => $inputs['classes']    ?? [],
                'deductions' => $inputs['deductions'] ?? [],
            ],
            'rules' => $ruleSnapshot,
            'currencies' => [
                'base_currency'    => $baseCurrency,
                'display_currency' => $displayCurrency,
                'fx'               => $fxSnapshot,
            ],
            'amounts' => [
                'gross_income'   => round($gross, 2),
                'deductions'     => round($dedTotal, 2),
                'reliefs'        => round($relTotal, 2),
                'taxable_income' => round($taxable, 2),
                'country_tax'    => round($country, 2),
                'state_tax'      => round($state, 2),
                'local_tax'      => round($local, 2),
                'total_tax'      => round($totalTax, 2),
                'credits'        => round($credits, 2),
                'net_tax_due'    => round($netTax, 2),
            ],
            'breakdown' => [
                'classes'    => $byDesc('taxClass')->values()->all(),
                'deductions' => $byDesc('deduction')->values()->all(),
                'reliefs'    => $byDesc('relief')->values()->all(),
                'tariffs'    => $byDesc('taxedIncomeByTariff')->values()->all(),
                'credits'    => $byDesc('withholdingCreditApplied')->values()->all(),
            ],
        ];

        // if statement already has display amounts (filled in service), keep them:
        if (!empty($tx->statement['amounts_display'])) {
            $payload['amounts_display'] = $tx->statement['amounts_display'];
        }

        return $payload;
    }
}
