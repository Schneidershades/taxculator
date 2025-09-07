<?php

namespace App\Services\Fx;

use App\Models\FxRate;
use Illuminate\Support\Facades\Cache;

class FxService
{
    public function resolve(string $base, string $quote, ?string $asOfDate = null): array
    {
        $base  = strtoupper($base);
        $quote = strtoupper($quote);

        // same currency
        if ($base === $quote) {
            return [
                'rate'       => 1.0,
                'as_of_date' => $asOfDate ?: now()->toDateString(),
                'source'     => 'identity',
                'pair'       => "{$base}/{$quote}",
            ];
        }

        $ttl = (int) config('fx.cache_ttl', 300);
        $key = "fx:{$base}:{$quote}:" . ($asOfDate ?: 'latest');

        return Cache::remember($key, $ttl, function () use ($base, $quote, $asOfDate) {
            // direct
            $row = FxRate::pair($base, $quote)->onOrBefore($asOfDate)->orderByDesc('as_of_date')->first();
            if ($row) {
                return [
                    'rate'       => (float) $row->rate,
                    'as_of_date' => $row->as_of_date->toDateString(),
                    'source'     => $row->source ?: 'db',
                    'pair'       => "{$base}/{$quote}",
                ];
            }

            // inverse (if we only have QUOTE→BASE)
            $inv = FxRate::pair($quote, $base)->onOrBefore($asOfDate)->orderByDesc('as_of_date')->first();
            if ($inv && (float) $inv->rate > 0) {
                return [
                    'rate'       => round(1 / (float) $inv->rate, 8),
                    'as_of_date' => $inv->as_of_date->toDateString(),
                    'source'     => ($inv->source ?: 'db') . ' (inverted)',
                    'pair'       => "{$base}/{$quote}",
                ];
            }

            // final fallback: 1.0 (safe no-op)
            return [
                'rate'       => 1.0,
                'as_of_date' => $asOfDate ?: now()->toDateString(),
                'source'     => 'fallback-1.0',
                'pair'       => "{$base}/{$quote}",
            ];
        });
    }

    /** Convert every numeric in $amounts by multiplying with $rate and rounding to given precision. */
    public function convertArray(array $amounts, float $rate, int $precision = 2): array
    {
        $out = [];
        foreach ($amounts as $k => $v) {
            $out[$k] = is_numeric($v) ? round(((float) $v) * $rate, $precision) : $v;
        }
        return $out;
    }
}
