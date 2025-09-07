<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Customer;
use App\Models\IngestionJob;
use App\Models\MappingProfile;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ParseEntityImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public string $entity,
        public string $path,
        public ?int $ingestionJobId = null,
    ) {}

    public function handle(): void
    {
        $jobRow = $this->ingestionJobId ? IngestionJob::find($this->ingestionJobId) : null;
        if ($jobRow) {
            $jobRow->update(['status' => 'processing', 'started_at' => now()]);
        }

        if (!Storage::exists($this->path)) {
            if ($jobRow) $jobRow->update(['status' => 'failed', 'finished_at' => now()]);
            return;
        }

        $mapping = null;
        if ($jobRow && ($mpId = ($jobRow->meta['mapping_profile_id'] ?? null))) {
            $mp = MappingProfile::find($mpId);
            $mapping = $mp?->mapping ?? null;
        }

        $stream = Storage::readStream($this->path);
        if (!$stream) {
            if ($jobRow) $jobRow->update(['status' => 'failed', 'finished_at' => now()]);
            return;
        }

        $fh = $stream;
        $header = null;
        $created = 0; $skipped = 0; $dupes = 0; $errs = 0;
        $errorRows = [];
        $docs = [];

        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) { $header = $this->normalizeHeader($data); continue; }
            if (count(array_filter($data, fn($v) => $v !== null && $v !== '')) === 0) { continue; }

            $row = array_combine($header, $data);
            // Aggregate documents for invoices/bills; handle others row-by-row
            if (in_array($this->entity, ['invoices','bills'], true)) {
                $m = fn($k) => $mapping[$k] ?? $k;
                $norm = fn($k) => $this->normalizeKey($k);
                $number = trim((string)($row[$norm($m('number'))] ?? ''));
                if ($number === '') { $errs++; $errorRows[] = array_merge($row, ['_reason' => 'Missing number']); continue; }
                $docs[$number][] = $row;
                continue;
            }

            [$ok, $reason] = $this->upsertRow($row, $mapping);
            if ($ok === true) {
                if ($reason === 'created') $created++; else $skipped++;
            } elseif ($ok === 'duplicate') {
                $dupes++;
            } else {
                $errs++;
                $errorRows[] = array_merge($row, ['_reason' => $reason]);
            }
        }
        fclose($fh);

        // Process aggregated invoices/bills as multi-line documents, posting once per document
        if (!empty($docs)) {
            foreach ($docs as $number => $rows) {
                if ($this->entity === 'invoices') {
                    [$ok, $reason] = $this->persistInvoiceDocument($number, $rows, $mapping);
                } else {
                    [$ok, $reason] = $this->persistBillDocument($number, $rows, $mapping);
                }
                if ($ok === true) {
                    if ($reason === 'created') $created++; else $skipped++;
                } elseif ($ok === 'duplicate') {
                    $dupes++;
                } else {
                    $errs++;
                    // attach last row as reference
                    $last = end($rows) ?: [];
                    $errorRows[] = array_merge($last, ['_reason' => $reason]);
                }
            }
        }

        $errorPath = null;
        if (!empty($errorRows)) {
            $errorDir = "imports_errors/{$this->tenantId}";
            $errorPath = $errorDir.'/errors_'.($this->ingestionJobId ?? 'unknown').'_'.time().'.csv';
            $out = fopen('php://temp', 'r+');
            if (!empty($errorRows)) {
                fputcsv($out, array_keys($errorRows[0]));
            }
            foreach ($errorRows as $er) { fputcsv($out, array_values($er)); }
            rewind($out);
            $content = stream_get_contents($out);
            fclose($out);
            Storage::put($errorPath, $content);
        }

        if ($jobRow) {
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

    private function upsertRow(array $row, ?array $mapping): array
    {
        $m = fn($k) => $mapping[$k] ?? $k;
        $norm = fn($k) => $this->normalizeKey($k);

        if ($this->entity === 'accounts') {
            $code = trim((string)($row[$norm($m('code'))] ?? ''));
            $name = trim((string)($row[$norm($m('name'))] ?? ''));
            if ($code === '' || $name === '') return [false, 'Missing code or name'];
            $type = strtolower((string)($row[$norm($m('type'))] ?? '')) ?: Account::TYPE_ASSET;
            $parentCode = trim((string)($row[$norm($m('parent_code'))] ?? ''));
            $isActiveRaw = $row[$norm($m('is_active'))] ?? '1';
            $isActive = in_array(strtolower((string)$isActiveRaw), ['1','true','yes','y'], true);

            $acc = Account::where('tenant_id', $this->tenantId)->where('code', $code)->first();
            $parentId = null;
            if ($parentCode !== '') {
                $parent = Account::where('tenant_id', $this->tenantId)->where('code', $parentCode)->first();
                $parentId = $parent?->id;
            }
            if ($acc) {
                $acc->update(['name' => $name, 'type' => $type, 'parent_id' => $parentId, 'is_active' => $isActive]);
                return [true, 'updated'];
            }
            Account::create(['tenant_id' => $this->tenantId, 'code' => $code, 'name' => $name, 'type' => $type, 'parent_id' => $parentId, 'is_active' => $isActive]);
            return [true, 'created'];
        }

        if ($this->entity === 'customers' || $this->entity === 'vendors') {
            $external = trim((string)($row[$norm($m('external_id'))] ?? '')) ?: null;
            $name = trim((string)($row[$norm($m('name'))] ?? ''));
            $email = trim((string)($row[$norm($m('email'))] ?? '')) ?: null;
            $phone = trim((string)($row[$norm($m('phone'))] ?? '')) ?: null;
            $tax = trim((string)($row[$norm($m('tax_id'))] ?? '')) ?: null;

            if ($name === '') return [false, 'Missing name'];

            $address = [
                'line1' => $row[$norm($m('address_line1'))] ?? null,
                'line2' => $row[$norm($m('address_line2'))] ?? null,
                'city' => $row[$norm($m('city'))] ?? null,
                'state' => $row[$norm($m('state'))] ?? null,
                'postal_code' => $row[$norm($m('postal_code'))] ?? null,
                'country' => $row[$norm($m('country'))] ?? null,
            ];
            $activeRaw = $row[$norm($m('active'))] ?? '1';
            $active = in_array(strtolower((string)$activeRaw), ['1','true','yes','y'], true);

            if ($this->entity === 'customers') {
                $model = $external
                    ? Customer::where('tenant_id', $this->tenantId)->where('external_id', $external)->first()
                    : Customer::where('tenant_id', $this->tenantId)->where('name', $name)->where('email', $email)->first();
                if ($model) {
                    $model->update(compact('name','email','phone','tax') + ['address' => $address, 'active' => $active]);
                    return [true, 'updated'];
                }
                Customer::create(['tenant_id' => $this->tenantId, 'external_id' => $external, 'name' => $name, 'email' => $email, 'phone' => $phone, 'tax_id' => $tax, 'address' => $address, 'active' => $active]);
                return [true, 'created'];
            } else {
                $model = $external
                    ? Vendor::where('tenant_id', $this->tenantId)->where('external_id', $external)->first()
                    : Vendor::where('tenant_id', $this->tenantId)->where('name', $name)->where('email', $email)->first();
                if ($model) {
                    $model->update(compact('name','email','phone','tax') + ['address' => $address, 'active' => $active]);
                    return [true, 'updated'];
                }
                Vendor::create(['tenant_id' => $this->tenantId, 'external_id' => $external, 'name' => $name, 'email' => $email, 'phone' => $phone, 'tax_id' => $tax, 'address' => $address, 'active' => $active]);
                return [true, 'created'];
            }
        }

        if ($this->entity === 'invoices' || $this->entity === 'bills' ) {
            // handled in aggregated pass
            return [true, 'updated'];
        }

        if ($this->entity === 'journals') {
            $date = (string)($row[$norm($m('date'))] ?? '');
            $memo = (string)($row[$norm($m('memo'))] ?? 'Imported journal');
            $acc = trim((string)($row[$norm($m('account_code'))] ?? ''));
            $dr = (float)($row[$norm($m('debit'))] ?? 0);
            $cr = (float)($row[$norm($m('credit'))] ?? 0);
            $contra = trim((string)($row[$norm($m('contra_account_code'))] ?? ''));
            if ($acc === '' || $contra === '') return [false,'Missing account/contra'];
            if ($dr > 0 && $cr > 0) return [false,'Both debit and credit present'];
            $amount = $dr > 0 ? $dr : $cr;
            if ($amount <= 0) return [false,'Zero amount'];
            $a1 = Account::where('tenant_id',$this->tenantId)->where('code',$acc)->first();
            $a2 = Account::where('tenant_id',$this->tenantId)->where('code',$contra)->first();
            if (!$a1 || !$a2) return [false,'Account not found'];
            $lines = $dr > 0
                ? [ ['account_id'=>$a1->id,'debit'=>$amount], ['account_id'=>$a2->id,'credit'=>$amount] ]
                : [ ['account_id'=>$a2->id,'debit'=>$amount], ['account_id'=>$a1->id,'credit'=>$amount] ];
            app(\App\Services\Ledger\LedgerService::class)->post($this->tenantId, [
                'external_ref'=>null,
                'narrative'=>$memo,
                'occurred_at'=>($date ?: now()->toDateString()).' 00:00:00',
            ], $lines);
            return [true,'created'];
        }

        return [false, 'Unsupported entity'];
    }

    private function normalizeHeader(array $row): array
    {
        return array_map(fn($h) => $this->normalizeKey((string)$h), $row);
    }

    private function normalizeKey(string $key): string
    {
        $k = strtolower(trim($key));
        return str_replace([' ', '-', '/'], '_', $k);
    }

    private function persistInvoiceDocument(string $number, array $rows, ?array $mapping): array
    {
        $m = fn($k) => $mapping[$k] ?? $k;
        $norm = fn($k) => $this->normalizeKey($k);
        $first = $rows[0] ?? [];
        $date = (string)($first[$norm($m('date'))] ?? '');
        $due = (string)($first[$norm($m('due_date'))] ?? '');
        $custExt = trim((string)($first[$norm($m('customer_external_id'))] ?? '')) ?: null;
        $custName = trim((string)($first[$norm($m('customer_name'))] ?? '')) ?: null;
        $currency = strtoupper((string)($first[$norm($m('currency'))] ?? '')) ?: null;
        if ($number === '' || ($custExt === null && $custName === null)) return [false, 'Missing number/customer'];

        $cust = $custExt
            ? \App\Models\Customer::where('tenant_id',$this->tenantId)->where('external_id',$custExt)->first()
            : \App\Models\Customer::where('tenant_id',$this->tenantId)->where('name',$custName)->first();
        if (!$cust) return [false, 'Customer not found'];

        $inv = \App\Models\Invoice::firstOrCreate(['tenant_id'=>$this->tenantId,'number'=>$number], [
            'date'=>$date ?: now()->toDateString(), 'due_date'=>$due ?: null, 'customer_id'=>$cust->id, 'currency'=>$currency, 'total'=>0, 'status'=>'imported',
        ]);

        // If already posted, treat as duplicate and skip
        if (!empty($inv->journal_transaction_id)) {
            return ['duplicate', 'already_posted'];
        }

        $total = (float)$inv->total;
        foreach ($rows as $row) {
            $accCode = trim((string)($row[$norm($m('account_code'))] ?? ''));
            $desc = (string)($row[$norm($m('description'))] ?? '');
            $qty = (float)($row[$norm($m('qty'))] ?? 1);
            $price = (float)($row[$norm($m('unit_price'))] ?? 0);
            if ($accCode === '') return [false, 'Missing account_code'];
            $incomeAcc = Account::where('tenant_id',$this->tenantId)->where('code',$accCode)->first();
            if (!$incomeAcc) return [false, 'Income account not found'];
            $amount = round($qty * $price, 2);
            // prevent duplicate lines by simple signature
            $sig = sha1($accCode.'|'.$desc.'|'.$qty.'|'.$price);
            $exists = \App\Models\InvoiceLine::where('invoice_id',$inv->id)->where('description',$desc)->where('amount',$amount)->exists();
            if ($exists) continue;
            \App\Models\InvoiceLine::create(['invoice_id'=>$inv->id,'account_id'=>$incomeAcc->id,'description'=>$desc,'qty'=>$qty,'unit_price'=>$price,'amount'=>$amount]);
            $total += $amount;
        }

        $inv->update(['total'=>round($total, 2)]);

        $ar = Account::where('tenant_id',$this->tenantId)->where('code','1200')->first();
        if (!$ar) return [false, 'AR control (1200) not found'];

        if ($total <= 0) return [false, 'Zero total'];
        $lines = [
            ['account_id'=>$ar->id,'debit'=>$total],
            ['account_id'=>null,'credit'=>0], // placeholder, will expand below per line account
        ];
        // For simplicity, post single income credit to first line's account if mixed accounts are not required
        $firstLine = \App\Models\InvoiceLine::where('invoice_id',$inv->id)->first();
        if (!$firstLine || !$firstLine->account_id) return [false, 'No income line'];
        $lines[1] = ['account_id'=>$firstLine->account_id,'credit'=>$total];

        $j = app(\App\Services\Ledger\LedgerService::class)->post($this->tenantId, [
            'external_ref'=>$inv->id,
            'narrative'=>'Import invoice '.$number,
            'occurred_at'=>($date ?: now()->toDateString()).' 00:00:00',
        ], $lines);
        $inv->update(['journal_transaction_id'=>$j->id,'status'=>'posted']);
        return [true,'created'];
    }

    private function persistBillDocument(string $number, array $rows, ?array $mapping): array
    {
        $m = fn($k) => $mapping[$k] ?? $k;
        $norm = fn($k) => $this->normalizeKey($k);
        $first = $rows[0] ?? [];
        $date = (string)($first[$norm($m('date'))] ?? '');
        $due = (string)($first[$norm($m('due_date'))] ?? '');
        $venExt = trim((string)($first[$norm($m('vendor_external_id'))] ?? '')) ?: null;
        $venName = trim((string)($first[$norm($m('vendor_name'))] ?? '')) ?: null;
        $currency = strtoupper((string)($first[$norm($m('currency'))] ?? '')) ?: null;
        if ($number === '' || ($venExt === null && $venName === null)) return [false, 'Missing number/vendor'];

        $vendor = $venExt
            ? \App\Models\Vendor::where('tenant_id',$this->tenantId)->where('external_id',$venExt)->first()
            : \App\Models\Vendor::where('tenant_id',$this->tenantId)->where('name',$venName)->first();
        if (!$vendor) return [false, 'Vendor not found'];

        $bill = \App\Models\Bill::firstOrCreate(['tenant_id'=>$this->tenantId,'number'=>$number], [
            'date'=>$date ?: now()->toDateString(), 'due_date'=>$due ?: null, 'vendor_id'=>$vendor->id, 'currency'=>$currency, 'total'=>0, 'status'=>'imported',
        ]);

        if (!empty($bill->journal_transaction_id)) {
            return ['duplicate','already_posted'];
        }

        $total = (float)$bill->total;
        foreach ($rows as $row) {
            $accCode = trim((string)($row[$norm($m('account_code'))] ?? ''));
            $desc = (string)($row[$norm($m('description'))] ?? '');
            $qty = (float)($row[$norm($m('qty'))] ?? 1);
            $price = (float)($row[$norm($m('unit_price'))] ?? 0);
            if ($accCode === '') return [false, 'Missing account_code'];
            $expenseAcc = Account::where('tenant_id',$this->tenantId)->where('code',$accCode)->first();
            if (!$expenseAcc) return [false, 'Expense account not found'];
            $amount = round($qty * $price, 2);
            $exists = \App\Models\BillLine::where('bill_id',$bill->id)->where('description',$desc)->where('amount',$amount)->exists();
            if ($exists) continue;
            \App\Models\BillLine::create(['bill_id'=>$bill->id,'account_id'=>$expenseAcc->id,'description'=>$desc,'qty'=>$qty,'unit_price'=>$price,'amount'=>$amount]);
            $total += $amount;
        }

        $bill->update(['total'=>round($total, 2)]);
        $ap = Account::where('tenant_id',$this->tenantId)->where('code','2100')->first();
        if (!$ap) return [false, 'AP control (2100) not found'];

        if ($total <= 0) return [false, 'Zero total'];
        $firstLine = \App\Models\BillLine::where('bill_id',$bill->id)->first();
        if (!$firstLine || !$firstLine->account_id) return [false, 'No expense line'];
        $lines = [
            ['account_id'=>$firstLine->account_id,'debit'=>$total],
            ['account_id'=>$ap->id,'credit'=>$total],
        ];
        $j = app(\App\Services\Ledger\LedgerService::class)->post($this->tenantId, [
            'external_ref'=>$bill->id,
            'narrative'=>'Import bill '.$number,
            'occurred_at'=>($date ?: now()->toDateString()).' 00:00:00',
        ], $lines);
        $bill->update(['journal_transaction_id'=>$j->id,'status'=>'posted']);
        return [true,'created'];
    }
}
