<?php

namespace App\Services\Tax;

use Carbon\Carbon;
use App\Models\VatRate;
use App\Models\VatReturn;
use App\Models\VatInvoice;
use App\Models\VatVersion;
use App\Models\VatInvoiceLine;
use App\Models\TaxJurisdiction;
use Illuminate\Support\Facades\DB;

class VatService
{
    /** Create an invoice (idempotent). */
    public function createInvoice(array $p): VatInvoice
    {
        return DB::transaction(function () use ($p) {
            // Idempotency short-circuit
            if (!empty($p['idempotency'])) {
                if ($existing = VatInvoice::where('idempotency_key', $p['idempotency'])->first()) {
                    return $existing->load('lines');
                }
            }

            // Derive period safely from issue_date
            try {
                $period = Carbon::parse($p['issue_date'])->format('Y-m');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Invalid issue_date; expected a valid date (e.g., 2025-05-10).');
            }

            // Resolve jurisdiction versions (country → state → local)
            $country = TaxJurisdiction::country($p['country_code'])->firstOrFail();
            $state   = !empty($p['state_code']) ? TaxJurisdiction::state($p['country_code'], $p['state_code'])->first() : null;
            $local   = (!empty($p['local_code']) && $state) ? TaxJurisdiction::local($p['country_code'], $p['state_code'], $p['local_code'])->first() : null;

            $versions = collect([$country, $state, $local])->filter()
                ->map(fn($j) => VatVersion::where('tax_jurisdiction_id', $j->id)
                    ->where('tax_year', (int)$p['tax_year'])
                    ->active()
                    ->first())
                ->filter()
                ->values();

            if ($versions->isEmpty()) {
                throw new \RuntimeException('No VAT version configured for this jurisdiction and year.');
            }

            /** @var VatVersion $ver */
            $ver = $versions->last();

            // Build a lowercase code → rate map for resilient lookup
            $rates = $ver->rates
                ->keyBy(fn($r) => strtolower($r->code));

            // Create invoice shell
            $invoice = VatInvoice::create([
                'direction'       => $p['direction'],
                'company_id'      => $p['company_id'] ?? null,
                'issue_date'      => $p['issue_date'],
                'period'          => $period,
                'country_code'    => $p['country_code'],
                'state_code'      => $p['state_code'] ?? null,
                'local_code'      => $p['local_code'] ?? null,
                'tax_year'        => (int)$p['tax_year'],
                'currency_code'   => $p['currency_code'] ?? null,
                'idempotency_key' => $p['idempotency'] ?? null,
            ]);

            $net = 0.0;
            $vat = 0.0;

            foreach ($p['lines'] as $line) {
                $cat   = strtolower((string)($line['category_code'] ?? 'standard'));
                $rate  = $rates->get($cat);

                if (!$rate) {
                    // Fail explicitly with the category that’s missing
                    throw new \RuntimeException("Unknown VAT category '{$cat}' for the selected version.");
                }

                $netAmt  = (float)$line['net_amount'];
                $vatRate = (float)$rate->rate_value;
                $reverse = (bool)($line['reverse_charge'] ?? false);

                $vatAmt = $rate->rate_type === 'percentage'
                    ? round($netAmt * ($vatRate / 100), 2)
                    : round($vatRate, 2);

                VatInvoiceLine::create([
                    'vat_invoice_id'      => $invoice->id,
                    'description'         => $line['description'] ?? null,
                    'category_code'       => $cat,
                    'net_amount'          => $netAmt,
                    'vat_rate'            => $vatRate,
                    'vat_amount'          => $vatAmt,
                    'reverse_charge'      => $reverse,
                    'place_of_supply_code' => $line['place_of_supply_code'] ?? null,
                ]);

                $net += $netAmt;
                $vat += $vatAmt;
            }

            // Reload lines once, then finalize totals & snapshot
            $lines = $invoice->lines()->get();

            $invoice->update([
                'net_total'   => round($net, 2),
                'vat_total'   => round($vat, 2),
                'gross_total' => round($net + $vat, 2),
                'statement'   => [
                    'version_id' => $ver->id,
                    'period'     => $period,
                    'lines'      => $lines->map(fn($l) => [
                        'desc' => $l->description,
                        'cat'  => $l->category_code,
                        'net'  => (float)$l->net_amount,
                        'rate' => (float)$l->vat_rate,
                        'vat'  => (float)$l->vat_amount,
                        'rc'   => (bool)$l->reverse_charge,
                    ])->all(),
                ],
            ]);

            return $invoice->fresh('lines');
        });
    }

    /** Return preview for a period & jurisdiction. */
    public function previewReturn(array $p): array
    {
        $q = VatInvoice::query()
            ->where('period', $p['period'])
            ->where('country_code', $p['country_code'])
            ->when(!empty($p['state_code']), fn($qq) => $qq->where('state_code', $p['state_code']))
            ->when(!empty($p['local_code']), fn($qq) => $qq->where('local_code', $p['local_code']));

        $invoices = $q->with('lines')->get();

        $output = 0.0;
        $input = 0.0;
        $sources = ['sales' => [], 'purchases' => []];

        foreach ($invoices as $inv) {
            $vatSum = (float)$inv->lines->sum('vat_amount');
            $hasRC  = (bool)$inv->lines->contains('reverse_charge', true);

            if ($inv->direction === 'sale') {
                $output += $vatSum;
                $sources['sales'][] = $inv->id;
                // reverse-charge on sales rarely used; ignoring special cases here
            } else {
                $input += $vatSum;
                $sources['purchases'][] = $inv->id;

                if ($hasRC) {
                    // reverse charge: add same VAT to output as if self-charged
                    $output += $vatSum;
                }
            }
        }

        $net = round($output - $input, 2);

        return [
            'amounts' => [
                'output_vat' => round($output, 2),
                'input_vat'  => round($input, 2),
                'net_vat'    => $net,
            ],
            'sources' => $sources,
        ];
    }

    /** File a return (idempotent by unique period+jurisdiction). */
    public function fileReturn(array $p): VatReturn
    {
        return DB::transaction(function () use ($p) {
            $preview = $this->previewReturn($p);

            $ret = VatReturn::updateOrCreate([
                'period'       => $p['period'],
                'country_code' => $p['country_code'],
                'state_code'   => $p['state_code'] ?? null,
                'local_code'   => $p['local_code'] ?? null,
            ], [
                'tax_year'   => (int)$p['tax_year'],
                'output_vat' => $preview['amounts']['output_vat'],
                'input_vat'  => $preview['amounts']['input_vat'],
                'net_vat'    => $preview['amounts']['net_vat'],
                'status'     => 'filed',
                'statement'  => [
                    'computed_at' => now()->toISOString(),
                    'sources'     => $preview['sources'],
                ],
            ]);

            return $ret->fresh();
        });
    }
}
