<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Tenant',
            'country_code' => 'NG',
            'base_currency' => 'NGN',
        ]);

        $make = fn(string $code, string $name, string $type) => Account::firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => $code,
        ], [
            'name' => $name,
            'type' => $type,
        ]);

        // Minimal chart
        $make('1000', 'Cash', Account::TYPE_ASSET);
        $make('1100', 'Bank', Account::TYPE_ASSET);
        $make('2000', 'Accounts Payable', Account::TYPE_LIABILITY);
        $make('3000', 'Owner Equity', Account::TYPE_EQUITY);
        $make('4000', 'Sales', Account::TYPE_INCOME);
        $make('5000', 'Cost of Goods Sold', Account::TYPE_EXPENSE);
        $make('5100', 'Operating Expenses', Account::TYPE_EXPENSE);
        $make('2200', 'VAT Payable', Account::TYPE_LIABILITY);
        $make('9999', 'Suspense', Account::TYPE_ASSET);
    }
}
