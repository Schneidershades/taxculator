<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Database\Seeders\ContributionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
    TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
    $this->seed(ContributionDemoSeeder::class);
});

test('employee and employer contributions are computed and returned', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => [
            'basic_salary' => 1_000_000,
            'housing'      =>   200_000,
            'clothing'     =>    50_000,
        ],
        'deductions'   => [],
    ];

    $res = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertCreated()
        ->json('data');

    expect($res['amounts'])->toHaveKeys(['employee_contrib', 'employer_contrib', 'total_tax']);
    expect((float)$res['amounts']['employee_contrib'])->toBeGreaterThan(0);
    expect((float)$res['amounts']['employer_contrib'])->toBeGreaterThan(0);

    // Breakdown arrays exist
    expect($res['breakdown'])->toHaveKeys(['employee_contributions', 'employer_contributions']);
    expect($res['breakdown']['employee_contributions'])->toBeArray();
    expect($res['breakdown']['employer_contributions'])->toBeArray();
});
