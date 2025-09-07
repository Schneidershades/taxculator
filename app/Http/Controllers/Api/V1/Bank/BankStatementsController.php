<?php

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Tenant;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BankStatementsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Tenancy::current();
        $q = BankStatement::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->orderByDesc('id');
        return $this->showAll($q->paginate(20));
    }

    public function show(int $id)
    {
        $tenant = Tenancy::current();
        $st = BankStatement::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->with('lines')->findOrFail($id);
        return $this->showOne($st);
    }

    public function store(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam file file required CSV with header: date, amount, description, counterparty.
        // @bodyParam bank_account_id integer Optional bank account id.
        $request->validate([
            'file' => ['required','file','mimes:csv,txt','max:10240'],
            'bank_account_id' => ['nullable','integer'],
        ]);

        /** @var Tenant|null $tenant */
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified. Include X-Tenant header.', 422);

        $bank = $this->resolveBankAccount($tenant, (int) $request->input('bank_account_id'));

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->storeAs("bank_statements/{$tenant->id}", uniqid('stmt_', true).'.csv');

        [$periodStart, $periodEnd, $lines] = $this->parseCsv($file->getRealPath());

        $st = BankStatement::create([
            'tenant_id' => $tenant->id,
            'bank_account_id' => $bank->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'currency_code' => $bank->currency_code ?? null,
            'status' => 'imported',
            'path' => $path,
        ]);

        foreach ($lines as $r) {
            BankStatementLine::create([
                'bank_statement_id' => $st->id,
                'posted_at' => $r['posted_at'],
                'amount' => $r['amount'],
                'description' => $r['description'] ?? null,
                'counterparty' => $r['counterparty'] ?? null,
                'external_id' => $r['external_id'] ?? null,
                'hash' => $this->sig($r['posted_at'].'|'.number_format((float)$r['amount'], 2, '.', '').'|'.($r['description'] ?? '')),
            ]);
        }

        return $this->respondSuccess(['message' => 'Statement imported.', 'data' => ['id' => $st->id, 'lines' => count($lines)]], 201);
    }

    protected function resolveBankAccount(Tenant $tenant, ?int $bankAccountId): BankAccount
    {
        if ($bankAccountId) {
            $bank = BankAccount::where('tenant_id', $tenant->id)->findOrFail($bankAccountId);
        } else {
            $bank = BankAccount::firstOrCreate([
                'tenant_id' => $tenant->id,
                'provider' => 'csv',
                'name' => 'CSV Upload',
            ], [
                'currency_code' => $tenant->base_currency ?? 'NGN',
            ]);
        }

        if (!$bank->ledger_account_id) {
            $ledger = Account::where('tenant_id', $tenant->id)->where('code', '1100')->first()
                ?: Account::where('tenant_id', $tenant->id)->where('code', '1000')->first();
            if ($ledger) {
                $bank->ledger_account_id = $ledger->id;
                $bank->save();
            }
        }

        return $bank;
    }

    private function parseCsv(string $filePath): array
    {
        $fh = fopen($filePath, 'r');
        $header = null;
        $rows = [];
        $minD = null; $maxD = null;
        while (($d = fgetcsv($fh)) !== false) {
            if ($header === null) { $header = $this->normalizeHeader($d); continue; }
            if (count(array_filter($d, fn($v) => $v !== null && $v !== '')) === 0) continue;
            $map = array_combine($header, $d);
            $date = $map['date'] ?? $map['posted_at'] ?? $map['transaction_date'] ?? null;
            $amt  = $map['amount'] ?? $map['amt'] ?? $map['value'] ?? null;
            $desc = $map['description'] ?? $map['narration'] ?? $map['details'] ?? null;
            $cp   = $map['counterparty'] ?? $map['beneficiary'] ?? $map['payee'] ?? null;
            $ts   = $this->parseDate($date);
            $minD = $minD ? min($minD, substr($ts,0,10)) : substr($ts,0,10);
            $maxD = $maxD ? max($maxD, substr($ts,0,10)) : substr($ts,0,10);
            $rows[] = ['posted_at' => $ts, 'amount' => (float) str_replace([','], '', (string)$amt), 'description' => $desc, 'counterparty' => $cp, 'external_id' => $map['id'] ?? null];
        }
        fclose($fh);
        return [$minD, $maxD, $rows];
    }

    private function normalizeHeader(array $row): array
    {
        return array_map(fn($h) => str_replace([' ','-','/'], '_', strtolower(trim((string)$h))), $row);
    }

    private function parseDate(?string $v): string
    {
        try { return \Carbon\Carbon::parse((string)$v)->toDateString().' 00:00:00'; }
        catch (\Throwable $e) { return now()->toDateString().' 00:00:00'; }
    }

    private function sig(string $s): string
    {
        return sha1($s);
    }
}

