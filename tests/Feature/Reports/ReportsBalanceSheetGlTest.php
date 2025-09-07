<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Services\Ledger\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

function seedBasicLedger(int $tenantId): void {
    // Minimal COA
    $cash = Account::create(['tenant_id' => $tenantId, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
    $ap   = Account::create(['tenant_id' => $tenantId, 'code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability']);
    $eq   = Account::create(['tenant_id' => $tenantId, 'code' => '3100', 'name' => 'Owner Equity', 'type' => 'equity']);

    // Post opening capital: Dr Cash 100,000; Cr Equity 100,000
    app(LedgerService::class)->post($tenantId, [
        'external_ref' => 'open', 'narrative' => 'Opening capital', 'occurred_at' => '2025-01-01 00:00:00',
    ], [
        ['account_id' => $cash->id, 'debit' => 100000],
        ['account_id' => $eq->id,   'credit'=> 100000],
    ]);

    // Record a bill not yet paid: Dr Expense (simulate using AP vs Cash) → we just increase AP and reduce cash for simplicity
    app(LedgerService::class)->post($tenantId, [
        'external_ref' => 'bill1', 'narrative' => 'Vendor bill', 'occurred_at' => '2025-01-10 00:00:00',
    ], [
        ['account_id' => $ap->id,   'credit'=> 20000],
        ['account_id' => $cash->id, 'debit' => 0],
    ]);
}

test('balance sheet export links and CSV download work', function () {
    $tenant = Tenant::create(['name' => 'Acme Inc', 'slug' => 'acme', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;

    seedBasicLedger($tenant->id);

    $res = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->getJson('/api/v1/reports/balance-sheet/export?as_of=2025-01-31')->assertOk()->json('data');

    expect($res['csv'])->toBeString();
    $csv = $this->get($res['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Assets');
});

test('general ledger export links and CSV download work', function () {
    $tenant = Tenant::create(['name' => 'Beta LLC', 'slug' => 'beta', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;

    seedBasicLedger($tenant->id);

    $res = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->getJson('/api/v1/reports/gl/export?from=2025-01-01&to=2025-01-31')->assertOk()->json('data');

    expect($res['csv'])->toBeString();
    $csv = $this->get($res['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Date'); // header sanity
});
