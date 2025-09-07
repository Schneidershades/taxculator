<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankTransaction;

uses(TestCase::class, RefreshDatabase::class);

function setupTenantData(): array {
    $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;
    $expense = Account::create(['tenant_id' => $tenant->id, 'code' => '5000', 'name' => 'Telecoms', 'type' => 'expense']);
    $bank = BankAccount::create(['tenant_id' => $tenant->id, 'name' => 'CSV Upload', 'provider' => 'csv', 'currency_code' => 'NGN']);
    return [$tenant, $user, $token, $expense, $bank];
}

test('rules apply categorizes matching transactions', function () {
    [$tenant, $user, $token, $expense, $bank] = setupTenantData();

    $t1 = BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => '1',
        'hash' => sha1('2025-01-02|-5000.00|MTN DATA'),
        'posted_at' => '2025-01-02 00:00:00',
        'amount' => -5000,
        'description' => 'MTN DATA',
        'status' => 'posted',
    ]);
    $t2 = BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => '2',
        'hash' => sha1('2025-01-03|-2000.00|GLO TOPUP'),
        'posted_at' => '2025-01-03 00:00:00',
        'amount' => -2000,
        'description' => 'GLO TOPUP',
        'status' => 'posted',
    ]);

    // Create rule: contains MTN -> Telecoms category
    $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->postJson('/api/v1/rules', [
            'name' => 'MTN to Telecoms',
            'matcher_type' => 'contains',
            'field' => 'description',
            'value' => 'MTN',
            'target_account_id' => $expense->id,
            'priority' => 10,
            'active' => true,
        ])->assertCreated();

    // Apply rule to both ids
    $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->postJson('/api/v1/rules/apply', ['ids' => [$t1->id, $t2->id]])
        ->assertOk();

    expect($t1->fresh()->category_account_id)->toEqual($expense->id);
    expect($t2->fresh()->category_account_id)->toBeNull();
});

test('bulk categorize updates multiple rows and locks them', function () {
    [$tenant, $user, $token, $expense, $bank] = setupTenantData();

    $t1 = BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => 'b1',
        'hash' => sha1('2025-01-05|-1500.00|b1'),
        'posted_at' => '2025-01-05 00:00:00',
        'amount' => -1500,
        'description' => 'b1',
        'status' => 'posted',
    ]);
    $t2 = BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => 'b2',
        'hash' => sha1('2025-01-06|-1800.00|b2'),
        'posted_at' => '2025-01-06 00:00:00',
        'amount' => -1800,
        'description' => 'b2',
        'status' => 'posted',
    ]);

    $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->putJson('/api/v1/transactions/bulk', [
            'ids' => [$t1->id, $t2->id],
            'category_account_id' => $expense->id,
            'lock' => true,
        ])->assertOk();

    expect($t1->fresh()->category_account_id)->toEqual($expense->id);
    expect($t2->fresh()->status)->toEqual('locked');
});
