<?php

namespace App\Services\Tax;

use App\Models\TaxJurisdiction;
use App\Models\CorporateTaxVersion;
use App\Models\CorporateTaxLoss;
use App\Models\CorporateTaxTransaction;
use Illuminate\Support\Facades\DB;

class CorporateTaxService
{
    /** Stateless preview. */
    public function preview(array $p): array
    {
        [$versions, $base] = $this->resolve($p);

        // apply FIFO losses if company_id present
        $lossUsed = 0.0;
        $lossLines = [];
        $taxableBase = $base;

        if (!empty($p['company_id'])) {
            [$lossUsed, $lossLines, $taxableBase] = $this->simulateLossUse((int)$p['company_id'], (int)$p['tax_year'], $base);
        }

        // pick most specific version (local > state > country)
        /** @var CorporateTaxVersion $v */
        $v = $versions->last();

        [$tax, $calc] = $this->applyRate($v, $taxableBase);

        $minTax = (float) ($v->minimum_tax_amount ?? 0);
        $payable = max($tax, $minTax);

        return [
            'amounts' => [
                'profit_before_adjustment' => round((float)$p['profit'], 2),
                'adjustments'              => round((float)($p['adjustments'] ?? 0), 2),
                'profit_after_adjustment'  => round($base, 2),
                'loss_relief_applied'      => round($lossUsed, 2),
                'taxable_profit'           => round($taxableBase, 2),
                'calculated_tax'           => round($tax, 2),
                'minimum_tax'              => round($minTax, 2),
                'tax_payable'              => round($payable, 2),
            ],
            'breakdown' => [
                'losses'     => $lossLines,
                'calculation' => $calc,
            ],
        ];
    }

    /** Persist a corporate tax transaction (idempotent). */
    public function register(array $p): CorporateTaxTransaction
    {
        return DB::transaction(function () use ($p) {
            // idempotency
            if (!empty($p['idempotency'])) {
                $existing = CorporateTaxTransaction::where('idempotency_key', $p['idempotency'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            [$versions, $base] = $this->resolve($p);

            // lock + consume losses if company
            $lossUsed = 0.0;
            $lossLines = [];
            $taxableBase = $base;

            if (!empty($p['company_id'])) {
                [$lossUsed, $lossLines, $taxableBase] = $this->consumeLosses((int)$p['company_id'], (int)$p['tax_year'], $base);
            }

            /** @var CorporateTaxVersion $v */
            $v = $versions->last();

            [$tax, $calc] = $this->applyRate($v, $taxableBase);

            $minTax = (float) ($v->minimum_tax_amount ?? 0);
            $payable = max($tax, $minTax);

            $versionSnapshot = [
                'version_id'   => $v->id,
                'jurisdiction' => [
                    'level'   => $v->jurisdiction->level ?? null,
                    'country' => $v->jurisdiction->country_code ?? null,
                    'state'   => $v->jurisdiction->state_code ?? null,
                    'local'   => $v->jurisdiction->local_code ?? null,
                ],
                'tax_year'     => $v->tax_year,
                'rate_type'    => $v->rate_type,
                'rate_value'   => (float)$v->rate_value,
                'minimum_tax'  => (float)($v->minimum_tax_amount ?? 0),
            ];
            $rulesHash = hash('sha256', json_encode($versionSnapshot));

            $inputs = [
                'country_code' => $p['country_code'] ?? null,
                'state_code'   => $p['state_code'] ?? null,
                'local_code'   => $p['local_code'] ?? null,
                'tax_year'     => (int)$p['tax_year'],
                'company_id'   => $p['company_id'] ?? null,
                'profit'       => (float)$p['profit'],
                'adjustments'  => (float)($p['adjustments'] ?? 0),
            ];

            $statement = [
                'meta' => [
                    'computed_at' => now()->toISOString(),
                    'version'     => 1,
                ],
                'inputs'   => $inputs,
                'version'  => $versionSnapshot,
                'amounts'  => [
                    'profit_before_adjustment' => round((float)$p['profit'], 2),
                    'adjustments'              => round((float)($p['adjustments'] ?? 0), 2),
                    'profit_after_adjustment'  => round($base, 2),
                    'loss_relief_applied'      => round($lossUsed, 2),
                    'taxable_profit'           => round($taxableBase, 2),
                    'calculated_tax'           => round($tax, 2),
                    'minimum_tax'              => round($minTax, 2),
                    'tax_payable'              => round($payable, 2),
                ],
                'breakdown' => [
                    'losses'     => $lossLines,
                    'calculation' => $calc,
                ],
            ];

            $tx = CorporateTaxTransaction::create([
                'company_id'       => $p['company_id'] ?? null,
                'idempotency_key'  => $p['idempotency'] ?? null,
                'input_snapshot'   => $inputs,
                'version_snapshot' => $versionSnapshot,
                'rules_hash'       => $rulesHash,
                'statement'        => $statement,
            ]);

            return $tx->fresh();
        });
    }

    // ---------- internals ----------

    private function resolve(array $p): array
    {
        $country = TaxJurisdiction::country($p['country_code'])->firstOrFail();
        $state   = !empty($p['state_code']) ? TaxJurisdiction::state($p['country_code'], $p['state_code'])->first() : null;
        $local   = (!empty($p['local_code']) && $state) ? TaxJurisdiction::local($p['country_code'], $p['state_code'], $p['local_code'])->first() : null;

        $versions = collect([$country, $state, $local])->filter()
            ->map(fn($j) => CorporateTaxVersion::where('tax_jurisdiction_id', $j->id)
                ->where('tax_year', (int)$p['tax_year'])
                ->active()
                ->first())
            ->filter()
            ->values();

        if ($versions->isEmpty()) {
            throw new \RuntimeException('No corporate tax versions found for the given year/jurisdiction');
        }

        $base = (float)$p['profit'] + (float)($p['adjustments'] ?? 0);

        return [$versions, max(0, $base)];
    }

    /** Simulate (no DB writes) how much loss would be used FIFO. */
    private function simulateLossUse(int $companyId, int $taxYear, float $base): array
    {
        $remaining = $base;
        $used = 0.0;
        $lines = [];

        $credits = CorporateTaxLoss::where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($taxYear) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->orderBy('tax_year') // FIFO by origin year
            ->get();

        foreach ($credits as $c) {
            if ($remaining <= 0) break;
            $use = min((float)$c->remaining_amount, $remaining);
            if ($use <= 0) continue;

            $used += $use;
            $remaining -= $use;
            $lines[] = ['loss_id' => $c->id, 'origin_year' => $c->tax_year, 'used' => round($use, 2)];
        }

        return [round($used, 2), $lines, max(0, round($base - $used, 2))];
    }

    /** Consume loss rows with row locks. */
    private function consumeLosses(int $companyId, int $taxYear, float $base): array
    {
        $remaining = $base;
        $used = 0.0;
        $lines = [];

        $credits = CorporateTaxLoss::where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($taxYear) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->orderBy('tax_year')
            ->lockForUpdate()
            ->get();

        foreach ($credits as $c) {
            if ($remaining <= 0) break;
            $use = min((float)$c->remaining_amount, $remaining);
            if ($use <= 0) continue;

            $c->remaining_amount = round((float)$c->remaining_amount - $use, 2);
            $c->save();

            $used += $use;
            $remaining -= $use;
            $lines[] = ['loss_id' => $c->id, 'origin_year' => $c->tax_year, 'used' => round($use, 2)];
        }

        return [round($used, 2), $lines, max(0, round($base - $used, 2))];
    }

    private function applyRate(CorporateTaxVersion $v, float $taxableBase): array
    {
        if ($v->rate_type === 'amount') {
            return [(float)$v->rate_value, [
                'method' => 'amount',
                'rate'   => (float)$v->rate_value,
            ]];
        }

        $tax = round($taxableBase * ((float)$v->rate_value / 100), 2);

        return [$tax, [
            'method' => 'percentage',
            'rate'   => (float)$v->rate_value,
            'base'   => round($taxableBase, 2),
        ]];
    }
}
