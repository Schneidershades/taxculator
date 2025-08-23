<?php

namespace App\Services\Tax;

use App\Models\TaxVersion;

class RuleSnapshotBuilder
{
    /** Build normalized rule snapshot for audit and hashing. */
    public static function build(array $versions): array
    {
        return array_map(function (TaxVersion $v) {
            return [
                'version_id' => $v->id,
                'jurisdiction' => [
                    'id'      => $v->tax_jurisdiction_id,
                    'level'   => $v->jurisdiction->level ?? null,
                    'country' => $v->jurisdiction->country_code ?? null,
                    'state'   => $v->jurisdiction->state_code ?? null,
                    'local'   => $v->jurisdiction->local_code ?? null,
                ],
                'year'     => $v->tax_year,
                'tariffs'  => $v->tariffs()->orderBy('ordering')
                    ->get(['ordering', 'bracket_min', 'bracket_max', 'rate_type', 'rate_value'])
                    ->toArray(),
                'deductions' => $v->deductionRules()->with('deductionClass:id,short_name')
                    ->get()
                    ->map(fn($r) => [
                        'class'        => $r->deductionClass->short_name ?? null,
                        'type'         => $r->deduction_type,
                        'value'        => (float) $r->value,
                        'combine_mode' => $r->combine_mode,
                        'base_classes' => $r->baseClasses()->pluck('short_name')->values()->all(),
                    ])->toArray(),
                'reliefs' => $v->reliefRules()->with('reliefClass:id,code,type')
                    ->get()
                    ->map(fn($r) => [
                        'code'         => $r->reliefClass->code ?? null,
                        'type'         => $r->relief_type,
                        'value'        => (float) $r->value,
                        'minimum'      => (float) $r->minimum_amount,
                        'maximum'      => (float) $r->maximum_amount,
                        'min_status'   => $r->minimum_status,
                        'max_status'   => $r->maximum_status,
                        'combine_mode' => $r->combine_mode,
                    ])->toArray(),
                'class_links' => $v->classLinks()->with('taxClass:id,short_name')
                    ->get()->map(fn($cl) => $cl->taxClass->short_name)->values()->all(),
            ];
        }, $versions);
    }

    public static function hash(array $snapshot): string
    {
        $json = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash('sha256', $json);
    }
}
