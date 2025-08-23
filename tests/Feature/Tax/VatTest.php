<?php
// tests/Feature/Tax/VatTest.php

use Tests\TestCase;
use Database\Seeders\VatDemoSeeder;
use Database\Seeders\TaxBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);
uses()->beforeEach(fn() => $this->withoutExceptionHandling());
beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
    $this->seed(VatDemoSeeder::class);
});

test('sale invoice computes standard 7.5% VAT', function () {
    $res = $this->withHeader('Idempotency-Key', 'vat-inv-1')
        ->postJson('/api/v1/vat/invoices', [
            'direction'    => 'sale',
            'issue_date'   => '2025-05-10',
            'period'       => '2025-05', // not required; derived, but fine to send
            'country_code' => 'NG',
            'tax_year'     => 2025,
            'lines'        => [
                ['category_code' => 'standard', 'net_amount' => 100000],
            ],
        ])->assertCreated()->json('data');

    expect((float)$res['vat_total'])->toEqual(7500.00);
    expect((float)$res['gross_total'])->toEqual(107500.00);
});

test('return preview nets output minus input', function () {
    // Sale
    $this->postJson('/api/v1/vat/invoices', [
        'direction'    => 'sale',
        'issue_date'   => '2025-06-05',
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'lines'        => [['category_code' => 'standard', 'net_amount' => 200000]],
    ])->assertCreated();

    // Purchase
    $this->postJson('/api/v1/vat/invoices', [
        'direction'    => 'purchase',
        'issue_date'   => '2025-06-12',
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'lines'        => [['category_code' => 'standard', 'net_amount' => 50000]],
    ])->assertCreated();

    $preview = $this->getJson('/api/v1/vat/returns/preview?country_code=NG&tax_year=2025&period=2025-06')
        ->assertOk()
        ->json('data.amounts');

    expect($preview['output_vat'])->toEqual(15000.00); // 7.5% of 200k
    expect($preview['input_vat'])->toEqual(3750.00); // 7.5% of 50k
    expect($preview['net_vat'])->toEqual(11250.00);
});
