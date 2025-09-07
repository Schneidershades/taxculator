<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\JournalTransaction;
use App\Services\Ledger\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ParseCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $bankAccountId,
        public string $path,
        public ?int $ingestionJobId = null,
    ) {}

    public function handle(LedgerService $ledger): void
    {
        $jobRow = null;
        $mapping = null;
        if ($this->ingestionJobId) {
            $jobRow = \App\Models\IngestionJob::find($this->ingestionJobId);
            if ($jobRow) {
                $jobRow->update(['status' => 'processing', 'started_at' => now()]);
                $mpId = $jobRow->meta['mapping_profile_id'] ?? null;
                if ($mpId) {
                    $mp = \App\Models\MappingProfile::find($mpId);
                    $mapping = $mp?->mapping ?? null;
                }
            }
        }

        if (!Storage::exists($this->path)) {
            if ($jobRow) $jobRow->update(['status' => 'failed', 'finished_at' => now()]);
            return; // nothing to do
        }

        $stream = Storage::readStream($this->path);
        if (!$stream) {
            return;
        }

        $fh = $stream;
        $header = null;
        $rows = [];
        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) { $header = $this->normalizeHeader($data); continue; }
            if (count(array_filter($data, fn($v) => $v !== null && $v !== '')) === 0) { continue; }
            $rows[] = $this->mapRow($header, $data, $mapping);
        }
        fclose($fh);

        // Accounts lookup
        $bankAccountId = $this->resolveAccountId('1100') ?? $this->resolveAccountId('1000');
        $suspenseId    = $this->resolveAccountId('9999');

        $created = 0; $skipped = 0; $dupes = 0; $errs = 0;
        $errorRows = [];
        foreach ($rows as $r) {
            $hash = sha1(($r['posted_at'] ?? '').'|'.number_format((float)$r['amount'], 2, '.', '').'|'.($r['description'] ?? ''));

            // Validate
            if (empty($r['posted_at']) || !is_numeric($r['amount'])) {
                $errs++;
                $errorRows[] = [
                    'posted_at'   => (string)($r['posted_at'] ?? ''),
                    'amount'      => (string)($r['amount'] ?? ''),
                    'description' => (string)($r['description'] ?? ''),
                    'counterparty'=> (string)($r['counterparty'] ?? ''),
                    'reason'      => empty($r['posted_at']) ? 'Invalid date' : 'Invalid amount',
                ];
                continue;
            }

            // Skip duplicates
            if (BankTransaction::where('tenant_id', $this->tenantId)
                ->where('bank_account_id', $this->bankAccountId)
                ->where('hash', $hash)->exists()) {
                $dupes++;
                continue;
            }

            $bt = BankTransaction::create([
                'tenant_id' => $this->tenantId,
                'bank_account_id' => $this->bankAccountId,
                'external_id' => $r['external_id'] ?? null,
                'hash' => $hash,
                'posted_at' => $r['posted_at'],
                'amount' => (float)$r['amount'],
                'description' => $r['description'] ?? null,
                'counterparty' => $r['counterparty'] ?? null,
                'raw' => $r,
                'status' => 'imported',
            ]);

            $created++;

            // Post to ledger: bank vs suspense
            if ($bankAccountId && $suspenseId) {
                $amt = (float)$r['amount'];
                $lines = $amt >= 0
                    ? [
                        ['account_id' => $bankAccountId, 'debit' => $amt],
                        ['account_id' => $suspenseId,    'credit'=> $amt],
                      ]
                    : [
                        ['account_id' => $suspenseId,    'debit' => abs($amt)],
                        ['account_id' => $bankAccountId, 'credit'=> abs($amt)],
                      ];

                /** @var JournalTransaction $j */
                $j = $ledger->post($this->tenantId, [
                    'external_ref' => $bt->id,
                    'narrative'    => $bt->description,
                    'occurred_at'  => $bt->posted_at,
                ], $lines);

                $bt->update([
                    'journal_transaction_id' => $j->id,
                    'status' => 'posted',
                ]);
            }
        }

        if ($jobRow) {
            // Write error CSV if any
            $errorPath = null;
            if (!empty($errorRows)) {
                $errorDir = "csv_errors/{$this->tenantId}";
                $errorPath = $errorDir.'/errors_'.($this->ingestionJobId ?? 'unknown').'_'.time().'.csv';
                $out = fopen('php://temp', 'r+');
                fputcsv($out, ['posted_at','amount','description','counterparty','reason']);
                foreach ($errorRows as $er) {
                    fputcsv($out, [$er['posted_at'],$er['amount'],$er['description'],$er['counterparty'],$er['reason']]);
                }
                rewind($out);
                $content = stream_get_contents($out);
                fclose($out);
                Storage::put($errorPath, $content);
            }

            $jobRow->update([
                'status' => 'completed',
                'created_count' => $created,
                'skipped_count' => $skipped,
                'duplicates_count' => $dupes,
                'errors_count' => $errs,
                'error_csv_path' => $errorPath,
                'finished_at' => now(),
            ]);
        }
    }

    private function normalizeHeader(array $row): array
    {
        return array_map(function ($h) {
            $h = strtolower(trim((string)$h));
            $h = str_replace([' ', '-', '/'], '_', $h);
            return $h;
        }, $row);
    }

    private function mapRow(array $header, array $row, ?array $mapping = null): array
    {
        $map = array_combine($header, $row);
        if ($mapping) {
            // use explicit mapping where provided
            $date = $map[$this->normalizeKey($mapping['date'] ?? '')] ?? null;
            $amount = $map[$this->normalizeKey($mapping['amount'] ?? '')] ?? null;
            $desc = $map[$this->normalizeKey($mapping['description'] ?? '')] ?? null;
            $cp = $map[$this->normalizeKey($mapping['counterparty'] ?? '')] ?? null;
        } else {
            $date = $map['date'] ?? $map['posted_at'] ?? $map['transaction_date'] ?? null;
            $amount = $map['amount'] ?? $map['amt'] ?? $map['value'] ?? null;
            $desc = $map['description'] ?? $map['narration'] ?? $map['details'] ?? null;
            $cp = $map['counterparty'] ?? $map['beneficiary'] ?? $map['payee'] ?? null;
        }
        return [
            'external_id' => $map['id'] ?? null,
            'posted_at' => $this->parseDate($date),
            'amount' => (float) str_replace([','], '', (string)$amount),
            'description' => $desc,
            'counterparty' => $cp,
        ];
    }

    private function normalizeKey(string $key): string
    {
        $k = strtolower(trim($key));
        return str_replace([' ', '-', '/'], '_', $k);
    }

    private function parseDate(?string $val): ?string
    {
        try {
            if ($val === null || trim((string)$val) === '') return null;
            return \Carbon\Carbon::parse((string)$val)->toDateString().' 00:00:00';
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveAccountId(string $code): ?int
    {
        $acc = Account::where('tenant_id', $this->tenantId)->where('code', $code)->first();
        return $acc?->id;
    }
}
