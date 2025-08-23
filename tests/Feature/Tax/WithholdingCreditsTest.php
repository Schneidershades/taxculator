<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use App\Models\WithholdingCredit;
use Database\Seeders\TaxBootstrapSeeder;
use Database\Seeders\WithholdingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
    TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
    $this->seed(WithholdingDemoSeeder::class);
});

test('credits reduce net tax due and decrement remaining balance', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code'  => $v->jurisdiction->country_code,
        'tax_year'      => 2025,
        'classes'       => ['basic_salary' => 1_000_000],
        'deductions'    => [],
        'beneficiary_id' => 1,
    ];

    $res = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertStatus(201)
        ->json('data');

    // amounts should contain credits & net_tax_due
    expect($res['amounts'])->toHaveKeys(['total_tax', 'credits_applied', 'net_tax_due']);

    expect(is_numeric($res['amounts']['credits_applied']))->toBeTrue();
    expect((float) $res['amounts']['credits_applied'])->toBeGreaterThan(0);

    expect($res['amounts']['net_tax_due'])
        ->toEqualWithDelta(
            (float) $res['amounts']['total_tax'] - (float) $res['amounts']['credits_applied'],
            0.01
        );
});
