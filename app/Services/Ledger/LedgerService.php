<?php

namespace App\Services\Ledger;

use App\Models\JournalEntry;
use App\Models\JournalTransaction;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a balanced journal transaction.
     *
     * @param int $tenantId
     * @param array{occurred_at?:string,external_ref?:string,narrative?:string} $meta
     * @param array<int,array{account_id:int,debit?:float,credit?:float,occurred_at?:string}> $lines
     */
    public function post(int $tenantId, array $meta, array $lines): JournalTransaction
    {
        // Guard: prevent posting into closed periods
        $occur = $meta['occurred_at'] ?? now();
        $period = substr((string) (is_string($occur) ? $occur : (string) now()), 0, 7); // YYYY-MM
        if (\App\Models\AccountingPeriod::where('tenant_id', $tenantId)->where('period', $period)->where('status', 'closed')->exists()) {
            throw new \RuntimeException('Posting blocked: accounting period '.$period.' is closed.');
        }

        if (empty($lines)) {
            throw new \InvalidArgumentException('No journal lines provided.');
        }

        $debits = 0.0; $credits = 0.0;
        foreach ($lines as $ln) {
            $debits  += (float)($ln['debit']  ?? 0);
            $credits += (float)($ln['credit'] ?? 0);
        }
        if (round($debits, 2) !== round($credits, 2)) {
            throw new \RuntimeException('Unbalanced journal: debits must equal credits.');
        }

        return DB::transaction(function () use ($tenantId, $meta, $lines) {
            $tx = JournalTransaction::create([
                'tenant_id'   => $tenantId,
                'external_ref'=> $meta['external_ref'] ?? null,
                'narrative'   => $meta['narrative'] ?? null,
                'occurred_at' => $meta['occurred_at'] ?? now(),
            ]);

            foreach ($lines as $ln) {
                JournalEntry::create([
                    'tenant_id'     => $tenantId,
                    'transaction_id'=> $tx->id,
                    'account_id'    => (int)$ln['account_id'],
                    'debit'         => (float)($ln['debit']  ?? 0),
                    'credit'        => (float)($ln['credit'] ?? 0),
                    'occurred_at'   => $ln['occurred_at'] ?? $tx->occurred_at,
                ]);
            }

            return $tx->load('entries');
        });
    }
}
