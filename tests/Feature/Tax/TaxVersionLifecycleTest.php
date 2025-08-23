<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn() => $this->seed(TaxBootstrapSeeder::class));

test('draft versions are ignored by API/calc', function () {
    $v = TaxVersion::first();
    $v->update(['status' => TaxVersion::STATUS_DRAFT]);

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 1_000_000],
        'deductions'   => [],
    ];

    $this->postJson('/api/v1/tax/tax-calculations', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tax_year']); // ✅
});

test('published versions are used by API/calc', function () {
    $v = TaxVersion::first();
    $v->publish();

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 1_000_000],
        'deductions'   => [],
    ];

    $this->postJson('/api/v1/tax/tax-calculations', $payload)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['amounts' => ['total_tax']]]);
});
