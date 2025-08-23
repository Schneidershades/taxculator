<?php

use Tests\TestCase;
use App\Models\CorporateTaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Database\Seeders\CorporateTaxDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
    $this->seed(CorporateTaxDemoSeeder::class);
    CorporateTaxVersion::query()->update(['status' => CorporateTaxVersion::STATUS_PUBLISHED]);
});

test('CIT preview computes simple 30% on profit', function () {
    $res = $this->postJson('/api/v1/cit/calculate', [
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'profit'       => 1_000_000,
    ])->assertOk()->json('data');

    expect($res['amounts']['tax_payable'])->toEqual(300000.00);
});

test('CIT transaction is idempotent via header', function () {
    $payload = [
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'profit'       => 2_000_000,
        'company_id'   => 1,
    ];

    $id1 = $this->withHeader('Idempotency-Key', 'idem-cit-1')
        ->postJson('/api/v1/cit/transactions', $payload)
        ->assertCreated()
        ->json('id');

    $id2 = $this->withHeader('Idempotency-Key', 'idem-cit-1')
        ->postJson('/api/v1/cit/transactions', $payload)
        ->assertOk(200)  // second time returns 200
        ->json('id');

    expect($id2)->toEqual($id1);
});
