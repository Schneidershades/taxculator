<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(TestCase::class, RefreshDatabase::class);

// beforeEach(function () {
//     $this->seed(TaxBootstrapSeeder::class);
// });


// beforeEach(function () {
//     $this->seed(TaxBootstrapSeeder::class); // your bootstrap seeder
//     TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
// });

beforeEach(function () {
    $this->artisan('db:seed', [
        '--class' => TaxBootstrapSeeder::class,
    ])->run();

    TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
});

test('idempotency returns the same transaction', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 1_000_000],
        'deductions'   => [],
    ];

    $key = 'idem-test-123';

    $firstId = $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertStatus(201)
        ->json('data.id');

    $secondId = $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertStatus(200)
        ->json('data.id');

    expect($secondId)->toEqual($firstId);
});

test('statement endpoint returns normalized statement', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 800_000],
        'deductions'   => [],
    ];

    $txId = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertStatus(201)
        ->json('data.id');

    $this->getJson("/api/v1/tax/tax-transactions/{$txId}/statement")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'meta' => ['transaction_id', 'identifier', 'version'],
                'inputs' => ['jurisdiction' => ['country_code', 'tax_year'], 'classes', 'deductions'],
                'rules',
                'amounts' => ['gross_income', 'taxable_income', 'total_tax'],
                'breakdown' => ['classes', 'deductions', 'reliefs', 'tariffs'],
            ],
        ]);
});
