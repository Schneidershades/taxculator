<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Tax\TaxTransactionService;
use App\Models\TaxTransactionRelation;
use App\Models\TaxDeductionRule;
use Database\Seeders\TaxBootstrapSeeder;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
});

/** helper */
function calc(array $payload)
{
    /** @var TaxTransactionService $svc */
    $svc = app(TaxTransactionService::class);
    return $svc->register($payload)->fresh('relations');
}

test('country-only PIT: totals and components are consistent', function () {
    $payload = [
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'classes'      => [
            'basic_salary' => 2_000_000,
            'housing'      => 500_000,
            'clothing'     => 100_000,
        ],
        'deductions' => [
            'pension'        => true,
            'nhf'            => true,
            'life_insurance' => false,
        ],
    ];

    $tx = calc($payload);

    $gross   = $tx->relations()->ofType('grossIncome')->sum('value');
    $taxable = $tx->relations()->ofType('taxableIncome')->sum('value');
    $country = $tx->relations()->ofType('countryTax')->sum('value');
    $state   = $tx->relations()->ofType('stateTax')->sum('value');
    $local   = $tx->relations()->ofType('localTax')->sum('value');
    $total   = $tx->relations()->ofType('totalTax')->sum('value');

    expect($gross)->toBeGreaterThan(0.0);
    expect($taxable)->toBeGreaterThan(0.0);
    expect($state)->toEqual(0.0);
    expect($local)->toEqual(0.0);
    expect($country)->toBeGreaterThan(0.0);
    expect($total)->toEqual($country);

    $deductions = $tx->relations()->ofType('deduction')->sum('value');
    $reliefs    = $tx->relations()->ofType('relief')->sum('value');

    expect(round($taxable, 2))->toEqual(round($gross - $deductions - $reliefs, 2));
});

test('state + local stacking: totalTax = country + state + local, NHF override applies', function () {
    $payload = [
        'country_code' => 'NG',
        'state_code'   => 'LA',
        'local_code'   => 'IKEJA',
        'tax_year'     => 2025,
        'classes'      => [
            'basic_salary' => 2_500_000,
            'housing'      => 600_000,
            'clothing'     => 120_000,
        ],
        'deductions' => [
            'pension' => true,
            'nhf'     => true,   // Lagos override: 2.0%
        ],
    ];

    $tx = calc($payload);

    $country = $tx->relations()->ofType('countryTax')->sum('value');
    $state   = $tx->relations()->ofType('stateTax')->sum('value');
    $local   = $tx->relations()->ofType('localTax')->sum('value');
    $total   = $tx->relations()->ofType('totalTax')->sum('value');

    expect(round($total, 2))->toEqual(round($country + $state + $local, 2));
    expect($state)->toBeGreaterThan(0.0);
    expect($local)->toBeGreaterThan(0.0);

    $base = 2_500_000 + 600_000 + 120_000;

    $rels = $tx->relations()
        ->where('description', 'deduction')
        ->where('tax_transaction_relationable_type', App\Models\TaxDeductionRule::class)
        ->get()
        ->map(function (TaxTransactionRelation $r) {
            $rule = TaxDeductionRule::with('deductionClass')->find($r->tax_transaction_relationable_id);
            return [
                'short' => optional($rule->deductionClass)->short_name,
                'value' => (float) $r->value,
            ];
        });

    $pension = $rels->firstWhere('short', 'pension');
    $nhf     = $rels->firstWhere('short', 'nhf');

    expect($pension)->not->toBeNull();
    expect($nhf)->not->toBeNull();

    expect(round($pension['value'], 2))->toEqual(round($base * 0.08, 2));   // 8%
    expect(round($nhf['value'], 2))->toEqual(round($base * 0.02, 2));       // 2.0%
});

test('monotonicity: higher income never yields lower tax', function () {
    $svc = app(App\Services\Tax\TaxTransactionService::class);

    $taxA = $svc->preview([
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'classes'      => ['basic_salary' => 1_200_000],
        'deductions'   => ['pension' => false, 'nhf' => false],
    ])['amounts']['total_tax'];

    $taxB = $svc->preview([
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'classes'      => ['basic_salary' => 1_200_001],
        'deductions'   => ['pension' => false, 'nhf' => false],
    ])['amounts']['total_tax'];

    expect($taxB)->toBeGreaterThanOrEqual($taxA);
});
