<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\TaxBootstrapSeeder;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
});

test('POST /api/v1/tax/calculate returns preview payload', function () {
    $payload = [
        'country_code' => 'NG',
        'state_code'   => 'LA',
        'local_code'   => 'IKEJA',
        'tax_year'     => 2025,
        'classes'      => [
            'basic_salary' => 2_000_000,
            'housing'      => 500_000,
            'clothing'     => 100_000,
        ],
        'deductions' => [
            'pension' => true,
            'nhf'     => true,
        ],
    ];

    $this->postJson('/api/v1/tax/tax-calculations', $payload)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Tax preview calculated successfully.')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'amounts' => ['gross_income', 'taxable_income', 'country_tax', 'state_tax', 'local_tax', 'total_tax'],
                'breakdown' => ['classes', 'deductions', 'reliefs', 'tariffs'],
            ],
        ]);
});

test('POST /api/v1/tax/transactions persists and returns resource', function () {
    $payload = [
        'country_code' => 'NG',
        'tax_year'     => 2025,
        'classes'      => ['basic_salary' => 1_000_000],
        'deductions'   => ['pension' => false, 'nhf' => false],
    ];

    $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertStatus(200) // showOne returns 200
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'amounts' => ['gross_income', 'taxable_income', 'country_tax', 'state_tax', 'local_tax', 'total_tax'],
                'breakdown' => ['classes', 'deductions', 'reliefs', 'tariffs'],
            ]
        ]);
});
