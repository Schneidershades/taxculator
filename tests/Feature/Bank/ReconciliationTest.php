<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\UploadedFile;

uses(TestCase::class, RefreshDatabase::class);

test('can import statement, reconcile, and export report', function () {
    $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;

    // Essential accounts and bank
    Account::create(['tenant_id' => $tenant->id, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
    $bank = BankAccount::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'provider' => 'csv', 'currency_code' => 'NGN']);

    // Create one bank transaction to match
    BankTransaction::create([
        'tenant_id' => $tenant->id,
        'bank_account_id' => $bank->id,
        'external_id' => 'tx1',
        'hash' => sha1('2025-01-05|1000.00|sale'),
        'posted_at' => '2025-01-05 00:00:00',
        'amount' => 1000,
        'description' => 'sale',
        'status' => 'posted',
    ]);

    // Prepare statement CSV with matching line
    $content = "date,amount,description\n2025-01-05,1000,sale\n";
    $file = UploadedFile::fake()->createWithContent('stmt.csv', $content);

    $st = $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->post('/api/v1/bank/statements', ['file' => $file, 'bank_account_id' => $bank->id])
        ->assertCreated()->json('data.id');

    $res = $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->postJson('/api/v1/bank/reconcile', ['statement_id' => $st, 'window_days' => 2])
        ->assertOk()->json('data');

    expect($res['matched'])->toBeGreaterThan(0);

    // export links
    $exp = $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Tenant' => $tenant->slug])
        ->getJson('/api/v1/reports/reconciliation/export?statement_id='.$st)
        ->assertOk()->json('data');

    $csv = $this->get($exp['csv'])->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Matched');
});

