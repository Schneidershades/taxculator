<?php

namespace App\Services\ArAp;

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Bill;
use App\Services\Ledger\LedgerService;
use Illuminate\Support\Facades\DB;

class ArApService
{
    public function recordReceipt(int $tenantId, array $data): ArReceipt
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $bank = BankAccount::where('tenant_id', $tenantId)->findOrFail((int)$data['bank_account_id']);
            $bankLedger = $bank->ledger_account_id;
            if (!$bankLedger) {
                throw new \RuntimeException('Bank account has no linked ledger account.');
            }

            $ar = Account::where('tenant_id', $tenantId)->where('code', '1200')->first();
            if (!$ar) throw new \RuntimeException('AR control (1200) not found');

            /** @var LedgerService $ledger */
            $ledger = app(LedgerService::class);
            $j = $ledger->post($tenantId, [
                'external_ref' => 'rcpt:'.uniqid(),
                'narrative' => 'Customer receipt',
                'occurred_at' => $data['date'].' 00:00:00',
            ], [
                ['account_id' => $bankLedger, 'debit' => (float)$data['amount']],
                ['account_id' => $ar->id,     'credit'=> (float)$data['amount']],
            ]);

            $rcpt = ArReceipt::create([
                'tenant_id' => $tenantId,
                'date' => $data['date'],
                'customer_id' => $data['customer_id'] ?? null,
                'bank_account_id' => $bank->id,
                'currency_code' => $data['currency_code'] ?? null,
                'amount' => (float)$data['amount'],
                'journal_transaction_id' => $j->id,
            ]);

            foreach (($data['allocations'] ?? []) as $al) {
                $inv = Invoice::where('tenant_id', $tenantId)->findOrFail((int)$al['invoice_id']);
                ArReceiptAllocation::create([
                    'ar_receipt_id' => $rcpt->id,
                    'invoice_id' => $inv->id,
                    'amount' => (float)$al['amount'],
                ]);
            }

            return $rcpt->fresh('allocations');
        });
    }

    public function recordPayment(int $tenantId, array $data): ApPayment
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $bank = BankAccount::where('tenant_id', $tenantId)->findOrFail((int)$data['bank_account_id']);
            $bankLedger = $bank->ledger_account_id;
            if (!$bankLedger) throw new \RuntimeException('Bank account has no linked ledger account.');

            $ap = Account::where('tenant_id', $tenantId)->where('code', '2100')->first();
            if (!$ap) throw new \RuntimeException('AP control (2100) not found');

            /** @var LedgerService $ledger */
            $ledger = app(LedgerService::class);
            $j = $ledger->post($tenantId, [
                'external_ref' => 'pmt:'.uniqid(),
                'narrative' => 'Vendor payment',
                'occurred_at' => $data['date'].' 00:00:00',
            ], [
                ['account_id' => $ap->id,      'debit' => (float)$data['amount']],
                ['account_id' => $bankLedger,  'credit'=> (float)$data['amount']],
            ]);

            $pmt = ApPayment::create([
                'tenant_id' => $tenantId,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'bank_account_id' => $bank->id,
                'currency_code' => $data['currency_code'] ?? null,
                'amount' => (float)$data['amount'],
                'journal_transaction_id' => $j->id,
            ]);

            foreach (($data['allocations'] ?? []) as $al) {
                $bill = Bill::where('tenant_id', $tenantId)->findOrFail((int)$al['bill_id']);
                ApPaymentAllocation::create([
                    'ap_payment_id' => $pmt->id,
                    'bill_id' => $bill->id,
                    'amount' => (float)$al['amount'],
                ]);
            }

            return $pmt->fresh('allocations');
        });
    }
}

