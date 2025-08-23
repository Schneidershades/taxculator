<?php

namespace App\Support;

use App\Models\TaxVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\TaggableStore;

class TaxRuleCache
{
    public static function key(int $versionId): string
    {
        return "tax:version:{$versionId}:bundle";
    }

    public static function tag(int $versionId): string
    {
        return "tax:version:{$versionId}";
    }

    private static function bustKey(int $versionId): string
    {
        return "tax:version:{$versionId}:bust";
    }

    private static function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    /**
     * PIT bundle: classes, deductions, reliefs, tariffs, contributions.
     * Returns a pure array so it’s serialization friendly.
     */
    public static function pitBundle(int $versionId, int $ttlSeconds = 3600): array
    {
        $baseKey = self::key($versionId);

        if (self::supportsTags()) {
            $tags = [self::tag($versionId)];
            return Cache::tags($tags)->remember($baseKey, $ttlSeconds, fn() => self::buildBundle($versionId));
        }

        // Fallback for non-taggable stores (array/file): versioned key + bust token
        $bust = (int) Cache::get(self::bustKey($versionId), 0);
        $key  = "{$baseKey}:v{$bust}";

        return Cache::remember($key, $ttlSeconds, fn() => self::buildBundle($versionId));
    }

    /** Bust everything for a version. */
    public static function flushForVersion(int $versionId): void
    {
        if (self::supportsTags()) {
            Cache::tags([self::tag($versionId)])->flush();
            return;
        }

        // Fallback: increment bust token so next read uses a fresh key
        $k = self::bustKey($versionId);
        $next = (int) Cache::get($k, 0) + 1;
        // keep for a long time; any TTL is fine since we only read the latest
        Cache::put($k, $next, now()->addYears(10));
    }

    /** Actually builds the bundle from Eloquent. */
    private static function buildBundle(int $versionId): array
    {
        /** @var TaxVersion $v */
        $v = TaxVersion::with([
            'jurisdiction',
            'classLinks.taxClass:id,short_name',
            'deductionRules.deductionClass:id,short_name',
            'deductionRules.baseClasses:id,short_name',
            'reliefRules.reliefClass:id,code',
            'tariffs',
            'contributionRules.baseClasses:id,short_name',
        ])->findOrFail($versionId);

        $classes = $v->classLinks->map(fn($cl) => [
            'id'    => $cl->tax_class_id,
            'short' => optional($cl->taxClass)->short_name,
        ])->filter(fn($r) => !empty($r['short']))->values()->all();

        $deductions = $v->deductionRules->map(function ($r) {
            return [
                'id'           => $r->id,
                'short'        => optional($r->deductionClass)->short_name,
                'type'         => $r->deduction_type,
                'value'        => (float) $r->value,
                'combine_mode' => $r->combine_mode ?? 'stack',
                'base_shorts'  => $r->baseClasses->pluck('short_name')->values()->all(),
            ];
        })->filter(fn($r) => !empty($r['short']))->values()->all();

        $reliefs = $v->reliefRules->map(function ($r) {
            return [
                'id'               => $r->id,
                'code'             => optional($r->reliefClass)->code,
                'relief_type'      => $r->relief_type,
                'value'            => (float) $r->value,
                'minimum_amount'   => (float) $r->minimum_amount,
                'maximum_amount'   => $r->maximum_amount === null ? null : (float) $r->maximum_amount,
                'minimum_status'   => $r->minimum_status,
                'maximum_status'   => $r->maximum_status,
                'combine_mode'     => $r->combine_mode ?? 'stack',
            ];
        })->values()->all();

        $tariffs = $v->tariffs->sortBy('ordering')->map(fn($t) => [
            'id'         => $t->id,
            'ordering'   => (int) $t->ordering,
            'min'        => (float) $t->bracket_min,
            'max'        => $t->bracket_max === null ? null : (float) $t->bracket_max,
            'rate_type'  => $t->rate_type,
            'rate_value' => (float) $t->rate_value,
        ])->values()->all();

        $contribs = $v->contributionRules->map(function ($r) {
            return [
                'id'              => $r->id,
                'name'            => $r->name,
                'base_type'       => $r->base_type,
                'base_shorts'     => $r->baseClasses->pluck('short_name')->values()->all(),
                'rate_type'       => $r->rate_type,
                'employee_rate'   => $r->employee_rate !== null ? (float) $r->employee_rate : null,
                'employer_rate'   => $r->employer_rate !== null ? (float) $r->employer_rate : null,
                'employee_floor'  => $r->employee_floor !== null ? (float) $r->employee_floor : null,
                'employer_floor'  => $r->employer_floor !== null ? (float) $r->employer_floor : null,
                'employee_cap'    => $r->employee_cap !== null ? (float) $r->employee_cap : null,
                'employer_cap'    => $r->employer_cap !== null ? (float) $r->employer_cap : null,
                'combine_mode'    => $r->combine_mode ?? 'stack',
            ];
        })->values()->all();

        return [
            'version_id'    => $v->id,
            'level'         => $v->jurisdiction->level,
            'classes'       => $classes,
            'deductions'    => $deductions,
            'reliefs'       => $reliefs,
            'tariffs'       => $tariffs,
            'contributions' => $contribs,
        ];
    }
}
