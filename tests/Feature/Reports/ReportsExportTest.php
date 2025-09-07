<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('can generate signed CSV link for P&L and download it', function () {
    // Setup tenant, user, accounts, and transactions
    $tenant = Tenant::create(['name' => 'Acme Inc', 'slug' => 'acme', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;

    $income = Account::create(['tenant_id' => $tenant->id, 'code' => '4000', 'name' => 'Sales', 'type' => 'income']);
    $expense = Account::create(['tenant_id' => $tenant->id, 'code' => '5000', 'name' => 'Office Supplies', 'type' => 'expense']);
    $bank = BankAccount::create(['tenant_id' => $tenant->id, 'name' => 'CSV Upload', 'provider' => 'csv', 'currency_code' => 'NGN']);

    BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => 'tx1',
        'hash' => sha1('2025-01-05|100000.00|sale'),
        'posted_at' => '2025-01-05 00:00:00',
        'amount' => 100000,
        'description' => 'sale',
        'category_account_id' => $income->id,
        'status' => 'posted',
    ]);
    BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => 'tx2',
        'hash' => sha1('2025-01-06|-25000.00|paper'),
        'posted_at' => '2025-01-06 00:00:00',
        'amount' => -25000,
        'description' => 'paper',
        'category_account_id' => $expense->id,
        'status' => 'posted',
    ]);

    $res = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->getJson('/api/v1/reports/pnl/export?from=2025-01-01&to=2025-01-31')
        ->assertOk()
        ->json('data');

    expect($res['csv'])->toBeString();

    $csv = $this->get($res['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Net');
});

