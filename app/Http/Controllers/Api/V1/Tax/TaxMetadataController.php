<?php

namespace App\Http\Controllers\Api\V1\Tax;

use App\Models\TaxTariff;
use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\TaxTariffIndexFormRequest;
use App\Http\Requests\Tax\TaxVersionIndexFormRequest;

class TaxMetadataController extends Controller
{
    /**
     * GET /api/v1/tax/versions
     * Returns versions for the most specific jurisdictions resolved from the query.
     */
    public function versions(TaxVersionIndexFormRequest $request)
    {
        [$country, $state, $local] = $this->resolveJurisdictions(
            $request->country_code,
            $request->state_code,
            $request->local_code
        );

        $queryYear = $request->tax_year;

        $versions = collect([$country, $state, $local])
            ->filter()
            ->flatMap(function ($j) use ($queryYear) {
                $q = TaxVersion::query()
                    ->with('jurisdiction')
                    ->where('tax_jurisdiction_id', $j->id);

                if ($queryYear) {
                    $q->where('tax_year', $queryYear);
                }
                return $q->get();
            });

        return $this->showAll($versions);
    }

    /**
     * GET /api/v1/tax/tariffs
     * Returns all tariff brackets for the resolved jurisdictions at the given year,
     * ordered by level (country/state/local) and ordering.
     */
    public function tariffs(TaxTariffIndexFormRequest $request)
    {
        [$country, $state, $local] = $this->resolveJurisdictions(
            $request->country_code,
            $request->state_code,
            $request->local_code
        );

        $year = (int) $request->tax_year;

        // Find versions per level
        $versions = collect([$country, $state, $local])
            ->filter()
            ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)
                ->where('tax_year', $year)->first())
            ->filter()
            ->values();

        if ($versions->isEmpty()) {
            return $this->respondError('No tax versions configured for this jurisdiction and year.', 404);
        }

        // Collect tariffs from each version and tag level
        $tariffs = new Collection();
        foreach ($versions as $v) {
            $level = $v->jurisdiction->level;
            $tariffs = $tariffs->merge(
                $v->tariffs()->orderBy('ordering')->get()
                    ->each(fn(TaxTariff $t) => $t->setAttribute('_level', $level))
            );
        }

        return $this->showAll($tariffs);
    }

    private function resolveJurisdictions(string $countryCode, ?string $stateCode, ?string $localCode): array
    {
        $country = TaxJurisdiction::country($countryCode)->firstOrFail();
        $state   = $stateCode ? TaxJurisdiction::state($countryCode, $stateCode)->first() : null;
        $local   = ($state && $localCode) ? TaxJurisdiction::local($countryCode, $stateCode, $localCode)->first() : null;
        return [$country, $state, $local];
    }
}
