<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
    TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
});

test('statement pdf downloads', function () {
    $v = TaxVersion::first();

    $id = $this->postJson('/api/v1/tax/tax-transactions', [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 500_000],
        'deductions'   => [],
    ])->assertCreated()->json('data.id');

    $this->get("/api/v1/tax/tax-transactions/{$id}/statement.pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
