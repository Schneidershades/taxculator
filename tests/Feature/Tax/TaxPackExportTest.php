<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\TaxBootstrapSeeder;
use Database\Seeders\CorporateTaxDemoSeeder;
use App\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
});

test('PIT pack export links and CSV download work', function () {
    // Create a PIT transaction (public route)
    $id = $this->postJson('/api/v1/tax/tax-transactions', [
        'country_code' => 'NG',
        'tax_year' => 2025,
        'classes' => ['basic_salary' => 1000000],
        'deductions' => ['pension' => false],
    ])->assertCreated()->json('data.id');

    // Auth user (verified) to retrieve pack links
    $user = User::factory()->create(['email_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    $res = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/tax/tax-transactions/{$id}/pack")
        ->assertOk()
        ->json('data');

    expect($res['csv'])->toBeString();
    $csv = $this->get($res['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Net tax due');
});

test('CIT pack export links and CSV download work', function () {
    $this->seed(CorporateTaxDemoSeeder::class);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    // Create CIT transaction
    $id = $this->withHeader('Idempotency-Key', 'cit-pack-1')
        ->postJson('/api/v1/cit/transactions', [
            'country_code' => 'NG',
            'tax_year' => 2025,
            'profit' => 500000,
        ])->assertCreated()->json('id');

    // Get pack links
    $res = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/cit/transactions/{$id}/pack")
        ->assertOk()
        ->json('data');

    expect($res['csv'])->toBeString();
    $csv = $this->get($res['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Tax payable');
});

