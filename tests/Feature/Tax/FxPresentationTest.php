<?php

use Tests\TestCase;
use App\Models\TaxVersion;
use Database\Seeders\TaxBootstrapSeeder;
use Database\Seeders\FxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);
uses()->beforeEach(fn() => $this->withoutExceptionHandling());

beforeEach(function () {
    // Core PIT + rates
    $this->artisan('db:seed', ['--class' => TaxBootstrapSeeder::class])->run();
    $this->artisan('db:seed', ['--class' => FxRateSeeder::class])->run();

    // Make versions usable by calc API
    TaxVersion::query()->update(['status' => TaxVersion::STATUS_PUBLISHED]);
});

test('transaction response includes converted amounts when currency_code is provided', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code'  => $v->jurisdiction->country_code,
        'tax_year'      => $v->tax_year,
        'classes'       => ['basic_salary' => 1_000_000],
        'deductions'    => [],
        'currency_code' => 'USD',
    ];

    $data = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->json('data');

    // Has both base & converted amounts + currency meta
    expect($data)->toHaveKeys(['amounts', 'amounts_display', 'meta']);

    $baseTotal = (float) $data['amounts']['total_tax'];
    $convTotal = (float) $data['amounts_display']['total_tax'];

    $curr = $data['meta']['currencies'];
    expect($curr)->toHaveKeys(['base_currency', 'display_currency', 'fx']);

    // base is NGN by default (migration default) and display is USD
    expect(strtoupper($curr['base_currency']))->toBe('NGN');
    expect($curr['display_currency'])->toBe('USD');

    // FX snapshot sanity
    expect($curr['fx'])->toBeArray();
    expect($curr['fx']['pair'])->toBe('NGN/USD');
    expect(is_numeric($curr['fx']['rate']))->toBeTrue();

    // Converted ≈ base * rate
    expect($convTotal)->toEqualWithDelta($baseTotal * (float) $curr['fx']['rate'], 0.02);
});

test('transaction response sticks to base currency when no currency_code is provided', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code' => $v->jurisdiction->country_code,
        'tax_year'     => $v->tax_year,
        'classes'      => ['basic_salary' => 500_000],
        'deductions'   => [],
        // no currency_code
    ];

    $data = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->json('data');

    // No converted amounts
    expect(isset($data['amounts_display']))->toBeFalse();

    $curr = $data['meta']['currencies'];
    expect($curr['display_currency'])->toBeNull();
    expect($curr['fx'])->toBeNull();
    expect(strtoupper($curr['base_currency']))->toBe('NGN');
});

test('statement JSON echoes currencies and converted amounts', function () {
    $v = TaxVersion::first();

    $payload = [
        'country_code'  => $v->jurisdiction->country_code,
        'tax_year'      => $v->tax_year,
        'classes'       => ['basic_salary' => 750_000],
        'deductions'    => [],
        'currency_code' => 'USD',
    ];

    $id = $this->postJson('/api/v1/tax/tax-transactions', $payload)
        ->assertCreated()
        ->json('data.id');

    $stmt = $this->getJson("/api/v1/tax/tax-transactions/{$id}/statement")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->json('data');

    // currencies block exists
    expect($stmt)->toHaveKey('currencies');
    expect($stmt['currencies']['base_currency'])->toBe('NGN');
    expect($stmt['currencies']['display_currency'])->toBe('USD');
    expect($stmt['currencies']['fx']['pair'])->toBe('NGN/USD');

    // amounts_display exists and matches rate
    expect($stmt)->toHaveKey('amounts_display');
    $rate = (float) $stmt['currencies']['fx']['rate'];
    expect((float) $stmt['amounts_display']['total_tax'])
        ->toEqualWithDelta((float) $stmt['amounts']['total_tax'] * $rate, 0.02);
});
