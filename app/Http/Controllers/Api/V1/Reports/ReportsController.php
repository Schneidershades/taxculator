<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Reports
 * Financial reports (P&L, Cashflow, Trial Balance) and export links.
 */
class ReportsController extends Controller
{
    public function balanceSheet(Request $request)
    {
        [$asOf, $data] = $this->computeBalanceSheet($request);
        return $this->respondSuccess(['message' => 'Balance sheet generated.', 'data' => array_merge(['as_of' => $asOf], $data)]);
    }
    public function pnl(Request $request)
    {
        [$from, $to, $data] = $this->computePnl($request);
        return $this->respondSuccess(['message' => 'P&L generated.', 'data' => array_merge(['period' => compact('from','to')], $data)]);
    }

    public function cashflow(Request $request)
    {
        [$from, $to, $data] = $this->computeCashflow($request);
        return $this->respondSuccess(['message' => 'Cashflow generated.', 'data' => array_merge(['period' => compact('from','to')], $data)]);
    }

    public function trialBalance(Request $request)
    {
        [$from, $to, $lines] = $this->computeTrialBalance($request);
        return $this->respondSuccess(['message' => 'Trial balance generated.', 'data' => [ 'period' => compact('from','to'), 'lines' => $lines ]]);
    }

    // ----- Exports (signed URLs and downloads) -----

    public function pnlExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $from = $request->query('from');
        $to   = $request->query('to');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.pnl.csv', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        $pdf = url()->temporarySignedRoute('rep.pnl.pdf', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function balanceSheetExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $asOf = $request->query('as_of');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.bs.csv', $ttl, ['tenant' => $tenant->slug, 'as_of' => $asOf]);
        $pdf = url()->temporarySignedRoute('rep.bs.pdf', $ttl, ['tenant' => $tenant->slug, 'as_of' => $asOf]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function cashflowExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $from = $request->query('from');
        $to   = $request->query('to');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.cashflow.csv', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        $pdf = url()->temporarySignedRoute('rep.cashflow.pdf', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function trialBalanceExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $from = $request->query('from');
        $to   = $request->query('to');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.tb.csv', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        $pdf = url()->temporarySignedRoute('rep.tb.pdf', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function glExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $from = $request->query('from');
        $to   = $request->query('to');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.gl.csv', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        $pdf = url()->temporarySignedRoute('rep.gl.pdf', $ttl, ['tenant' => $tenant->slug, 'from' => $from, 'to' => $to]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function reconciliationExportLinks(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $statementId = $request->query('statement_id');
        $ttl = now()->addMinutes(5);
        $csv = url()->temporarySignedRoute('rep.recon.csv', $ttl, ['tenant' => $tenant->slug, 'statement' => $statementId]);
        $pdf = url()->temporarySignedRoute('rep.recon.pdf', $ttl, ['tenant' => $tenant->slug, 'statement' => $statementId]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => ['csv' => $csv, 'pdf' => $pdf]]);
    }

    public function downloadPnlCsv(Request $request, string $tenant)
    {
        [$from, $to, $data] = $this->computePnl($request);
        $csv = $this->csv([['Metric','Amount'], ['Income', $data['income']], ['Expenses', $data['expenses']], ['Net', $data['net']]]);
        $filename = "pnl_{$from}_{$to}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadBalanceSheetCsv(Request $request, string $tenant)
    {
        [$asOf, $data] = $this->computeBalanceSheet($request);
        $rows = [['Section','Name','Amount']];
        foreach (['assets','liabilities','equity'] as $sec) {
            foreach ($data[$sec]['lines'] as $l) {
                $rows[] = [ucfirst($sec), $l['name'], (float)$l['amount']];
            }
            $rows[] = [ucfirst($sec).' Total','', (float)$data[$sec]['total']];
        }
        $rows[] = ['Balance Check (A = L + E)','', (float)$data['assets']['total'] - (float)$data['liabilities']['total'] - (float)$data['equity']['total']];
        $csv = $this->csv($rows);
        $filename = "balance_sheet_{$asOf}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadBalanceSheetPdf(Request $request, string $tenant)
    {
        [$asOf, $data] = $this->computeBalanceSheet($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.balance_sheet', ['asOf' => $asOf, 'data' => $data]);
        return $pdf->download("balance_sheet_{$asOf}.pdf");
    }

    public function downloadPnlPdf(Request $request, string $tenant)
    {
        [$from, $to, $data] = $this->computePnl($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pnl', ['from' => $from, 'to' => $to, 'data' => $data]);
        return $pdf->download("pnl_{$from}_{$to}.pdf");
    }

    public function downloadCashflowCsv(Request $request, string $tenant)
    {
        [$from, $to, $data] = $this->computeCashflow($request);
        $csv = $this->csv([['Metric','Amount'], ['Inflows', $data['inflows']], ['Outflows', $data['outflows']], ['Net Cash', $data['net_cash']]]);
        $filename = "cashflow_{$from}_{$to}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadCashflowPdf(Request $request, string $tenant)
    {
        [$from, $to, $data] = $this->computeCashflow($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.cashflow', ['from' => $from, 'to' => $to, 'data' => $data]);
        return $pdf->download("cashflow_{$from}_{$to}.pdf");
    }

    public function downloadTrialBalanceCsv(Request $request, string $tenant)
    {
        [$from, $to, $lines] = $this->computeTrialBalance($request);
        $rows = [['Code','Name','Debit','Credit']];
        foreach ($lines as $l) { $rows[] = [$l->code, $l->name, (float)$l->debit, (float)$l->credit]; }
        $csv = $this->csv($rows);
        $filename = "trial_balance_{$from}_{$to}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadTrialBalancePdf(Request $request, string $tenant)
    {
        [$from, $to, $lines] = $this->computeTrialBalance($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.trial_balance', ['from' => $from, 'to' => $to, 'lines' => $lines]);
        return $pdf->download("trial_balance_{$from}_{$to}.pdf");
    }

    public function gl(Request $request)
    {
        [$from, $to, $entries] = $this->computeGl($request);
        return $this->respondSuccess(['message' => 'General ledger generated.', 'data' => ['period' => compact('from','to'), 'entries' => $entries]]);
    }

    public function downloadGlCsv(Request $request, string $tenant)
    {
        [$from, $to, $entries] = $this->computeGl($request);
        $rows = [['Date','Txn ID','Account Code','Account Name','Debit','Credit','Narrative']];
        foreach ($entries as $e) { $rows[] = [$e->occurred_at, (string)$e->transaction_id, $e->code, $e->name, (float)$e->debit, (float)$e->credit, $e->narrative]; }
        $csv = $this->csv($rows);
        $filename = "gl_{$from}_{$to}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadGlPdf(Request $request, string $tenant)
    {
        [$from, $to, $entries] = $this->computeGl($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.gl', ['from' => $from, 'to' => $to, 'entries' => $entries]);
        return $pdf->download("gl_{$from}_{$to}.pdf");
    }

    public function downloadReconciliationCsv(Request $request, string $tenant)
    {
        $statementId = (int) $request->query('statement');
        $st = \App\Models\BankStatement::with('lines')->findOrFail($statementId);
        $rows = [['Type','Date','Amount','Description','Counterparty','Bank Txn ID']];
        foreach ($st->lines as $l) {
            $rows[] = [
                $l->matched_bank_transaction_id ? 'Matched' : 'Unmatched',
                optional($l->posted_at)->toDateString(),
                (float)$l->amount,
                (string)$l->description,
                (string)$l->counterparty,
                $l->matched_bank_transaction_id ? (string)$l->matched_bank_transaction_id : '',
            ];
        }
        $csv = $this->csv($rows);
        $filename = "reconciliation_{$st->id}.csv";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    public function downloadReconciliationPdf(Request $request, string $tenant)
    {
        $statementId = (int) $request->query('statement');
        $st = \App\Models\BankStatement::with('lines')->findOrFail($statementId);
        $matched = $st->lines->whereNotNull('matched_bank_transaction_id')->count();
        $unmatched = $st->lines->whereNull('matched_bank_transaction_id')->count();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.reconciliation', [
            'st' => $st,
            'matched' => $matched,
            'unmatched' => $unmatched,
        ]);
        return $pdf->download("reconciliation_{$st->id}.pdf");
    }

    // ----- Internals -----

    private function computePnl(Request $request): array
    {
        $tenant = Tenancy::current();
        $from = $request->query('from');
        $to   = $request->query('to');
        $q = BankTransaction::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->when($from, fn($qq) => $qq->where('posted_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('posted_at', '<=', $to))
            ->whereNotNull('category_account_id')
            ->join('accounts', 'accounts.id', '=', 'bank_transactions.category_account_id');
        $income = (float) $q->clone()->where('accounts.type', 'income')->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));
        $expenses = (float) $q->clone()->where('accounts.type', 'expense')->sum(DB::raw('CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END'));
        return [$from, $to, ['income' => round($income, 2), 'expenses' => round($expenses, 2), 'net' => round($income - $expenses, 2)]];
    }

    private function computeCashflow(Request $request): array
    {
        $tenant = Tenancy::current();
        $from = $request->query('from');
        $to   = $request->query('to');
        $q = BankTransaction::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->when($from, fn($qq) => $qq->where('posted_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('posted_at', '<=', $to));
        $inflows = (float) $q->clone()->where('amount', '>', 0)->sum('amount');
        $outflows = (float) $q->clone()->where('amount', '<', 0)->sum(DB::raw('ABS(amount)'));
        return [$from, $to, ['inflows' => round($inflows, 2), 'outflows' => round($outflows, 2), 'net_cash' => round($inflows - $outflows, 2)]];
    }

    private function computeTrialBalance(Request $request): array
    {
        $tenant = Tenancy::current();
        $from = $request->query('from');
        $to   = $request->query('to');
        $lines = DB::table('journal_entries as je')
            ->join('accounts as a', 'a.id', '=', 'je.account_id')
            ->when($tenant, fn($qq) => $qq->where('je.tenant_id', $tenant->id))
            ->when($from, fn($qq) => $qq->where('je.occurred_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('je.occurred_at', '<=', $to))
            ->select('a.id', 'a.code', 'a.name', DB::raw('SUM(je.debit) as debit'), DB::raw('SUM(je.credit) as credit'))
            ->groupBy('a.id','a.code','a.name')
            ->orderBy('a.code')
            ->get();
        return [$from, $to, $lines];
    }

    private function computeBalanceSheet(Request $request): array
    {
        $tenant = Tenancy::current();
        $asOf = $request->query('as_of') ?: $request->query('to') ?: now()->toDateString();

        $lines = \DB::table('journal_entries as je')
            ->join('accounts as a', 'a.id', '=', 'je.account_id')
            ->when($tenant, fn($qq) => $qq->where('je.tenant_id', $tenant->id))
            ->where('je.occurred_at', '<=', $asOf)
            ->select('a.id', 'a.code', 'a.name', 'a.type', \DB::raw('SUM(je.debit) as debit'), \DB::raw('SUM(je.credit) as credit'))
            ->groupBy('a.id','a.code','a.name','a.type')
            ->orderBy('a.code')
            ->get();

        $sections = [
            'assets'      => ['types' => ['asset'],    'lines' => [], 'total' => 0.0],
            'liabilities' => ['types' => ['liability'],'lines' => [], 'total' => 0.0],
            'equity'      => ['types' => ['equity'],   'lines' => [], 'total' => 0.0],
        ];

        foreach ($lines as $l) {
            $balance = match ($l->type) {
                'asset', 'expense' => (float)$l->debit - (float)$l->credit,
                default            => (float)$l->credit - (float)$l->debit,
            };
            foreach ($sections as $k => &$sec) {
                if (in_array($l->type, $sec['types'], true)) {
                    $sec['lines'][] = ['code' => $l->code, 'name' => $l->name, 'amount' => round($balance, 2)];
                    $sec['total']   = round($sec['total'] + $balance, 2);
                }
            }
        }

        return [$asOf, $sections];
    }

    private function computeGl(Request $request): array
    {
        $tenant = Tenancy::current();
        $from = $request->query('from');
        $to   = $request->query('to');
        $entries = \DB::table('journal_entries as je')
            ->join('accounts as a', 'a.id', '=', 'je.account_id')
            ->join('journal_transactions as jt', 'jt.id', '=', 'je.transaction_id')
            ->when($tenant, fn($qq) => $qq->where('je.tenant_id', $tenant->id))
            ->when($from, fn($qq) => $qq->where('je.occurred_at', '>=', $from))
            ->when($to, fn($qq) => $qq->where('je.occurred_at', '<=', $to))
            ->select('je.transaction_id','je.debit','je.credit','je.occurred_at','a.code','a.name','jt.narrative')
            ->orderBy('je.occurred_at')
            ->orderBy('je.transaction_id')
            ->get();
        return [$from, $to, $entries];
    }

    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        foreach ($rows as $row) { fputcsv($out, $row); }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return (string) $csv;
    }
}
