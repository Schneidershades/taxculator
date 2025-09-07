<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\CorporateTaxTransaction;
use App\Models\VatReturn;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Dashboard
 * Returns summary totals for a period: income, expenses, net, tax due.
 */
class SummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenant = Tenancy::current();
        $from = $request->query('from');
        $to   = $request->query('to');

        $bt = BankTransaction::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->when($from, fn($qq) => $qq->where('posted_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('posted_at', '<=', $to))
            ->whereNotNull('category_account_id');

        // Income = inflows categorized to income accounts (positive amounts)
        $income = (float) $bt->clone()
            ->join('accounts', 'accounts.id', '=', 'bank_transactions.category_account_id')
            ->where('accounts.type', 'income')
            ->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));

        // Expenses = outflows categorized to expense accounts (absolute value of negative amounts)
        $expenses = (float) $bt->clone()
            ->join('accounts', 'accounts.id', '=', 'bank_transactions.category_account_id')
            ->where('accounts.type', 'expense')
            ->sum(DB::raw('CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END'));

        $net = round($income - $expenses, 2);

        // Tax due: VAT filed in range + CIT created in range + PIT created in range
        $vatDue = (float) VatReturn::query()
            ->when($from && $to, function ($qq) use ($from, $to) {
                // period format YYYY-MM; include those where first day within range
                $qq->whereBetween(DB::raw("STRFTIME('%Y-%m-01', period||'-01')"), [$from, $to]);
            })
            ->where('status', 'filed')
            ->sum('net_vat');

        $citDue = (float) CorporateTaxTransaction::query()
            ->when($from, fn($qq) => $qq->where('created_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('created_at', '<=', $to))
            ->sum(DB::raw("COALESCE(json_extract(statement, '$.amounts.tax_payable'), 0)"));

        $pitDue = (float) DB::table('tax_transactions')
            ->when($from, fn($qq) => $qq->where('created_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('created_at', '<=', $to))
            ->sum(DB::raw("COALESCE(json_extract(statement, '$.amounts.net_tax_due'), 0)"));

        $taxDue = round($vatDue + $citDue + $pitDue, 2);

        return $this->respondSuccess([
            'message' => 'Summary computed successfully.',
            'data' => [
                'totals' => [
                    'income' => round($income, 2),
                    'expenses' => round($expenses, 2),
                    'net' => $net,
                    'tax_due' => $taxDue,
                ],
            ],
        ]);
    }
}
