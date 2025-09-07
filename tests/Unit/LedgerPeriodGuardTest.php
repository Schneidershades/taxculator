<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Services\Ledger\LedgerService;

uses(TestCase::class, RefreshDatabase::class);

test('ledger posting is blocked in closed period', function () {
    $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'country_code' => 'NG']);
    // simple accounts
    $cash = Account::create(['tenant_id' => $tenant->id, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
    $inc  = Account::create(['tenant_id' => $tenant->id, 'code' => '4000', 'name' => 'Sales', 'type' => 'income']);

    AccountingPeriod::create(['tenant_id' => $tenant->id, 'period' => '2025-01', 'status' => 'closed', 'closed_at' => now()]);

    $svc = app(LedgerService::class);
    $fn = fn() => $svc->post($tenant->id, [
        'external_ref' => 't1', 'narrative' => 'Test', 'occurred_at' => '2025-01-15 00:00:00',
    ], [
        ['account_id' => $cash->id, 'debit' => 100],
        ['account_id' => $inc->id,  'credit'=> 100],
    ]);

    expect($fn)->toThrow(RuntimeException::class);
});

