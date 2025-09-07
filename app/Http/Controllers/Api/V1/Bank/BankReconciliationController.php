<?php

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BankReconciliationController extends Controller
{
    public function reconcile(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam statement_id integer required Bank statement id.
        // @bodyParam window_days integer Match window in days. Example: 3
        // @bodyParam auto_clear boolean Whether to mark as cleared on match. Example: true
        $payload = $request->validate([
            'statement_id' => ['required','integer','min:1'],
            'window_days' => ['nullable','integer','min:0'],
            'auto_clear' => ['sometimes','boolean'],
        ]);

        $tenant = Tenancy::current();
        $st = BankStatement::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->findOrFail($payload['statement_id']);

        $lines = $st->lines()->whereNull('matched_bank_transaction_id')->get();
        $window = (int) ($payload['window_days'] ?? 3);
        $auto = (bool) ($payload['auto_clear'] ?? true);
        $matched = 0; $unmatched = 0;

        foreach ($lines as $line) {
            $date = Carbon::parse($line->posted_at);
            $from = $date->copy()->subDays($window);
            $to   = $date->copy()->addDays($window);

            $tx = BankTransaction::query()
                ->where('tenant_id', $st->tenant_id)
                ->where('bank_account_id', $st->bank_account_id)
                ->whereBetween('posted_at', [$from, $to])
                ->where('amount', (float)$line->amount)
                ->whereNull('cleared_at')
                ->first();

            if ($tx) {
                $line->update(['matched_bank_transaction_id' => $tx->id, 'matched_at' => now()]);
                if ($auto) {
                    $tx->update(['cleared_at' => now(), 'cleared_statement_id' => $st->id]);
                }
                $matched++;
            } else {
                $unmatched++;
            }
        }

        $st->update(['status' => 'reconciled']);

        return $this->respondSuccess(['message' => 'Reconciliation completed.', 'data' => compact('matched','unmatched')]);
    }
}

