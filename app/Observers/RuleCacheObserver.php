<?php

namespace App\Observers;

use App\Support\TaxRuleCache;
use App\Models\TaxVersion;
use App\Models\TaxTariff;
use App\Models\TaxDeductionRule;
use App\Models\TaxReliefRule;
use App\Models\ContributionRule;
use App\Models\VatVersion;
use App\Models\VatRate;
use App\Models\CorporateTaxVersion;

class RuleCacheObserver
{
    public function saved($model): void
    {
        $this->bust($model);
    }
    public function deleted($model): void
    {
        $this->bust($model);
    }

    private function bust($model): void
    {
        if ($model instanceof TaxVersion) {
            TaxRuleCache::bustPit($model->id);
        }
        if ($model instanceof TaxTariff || $model instanceof TaxDeductionRule || $model instanceof TaxReliefRule || $model instanceof ContributionRule) {
            TaxRuleCache::bustPit($model->tax_version_id);
        }
        if ($model instanceof VatVersion) {
            TaxRuleCache::bustVat($model->id);
        }
        if ($model instanceof VatRate) {
            TaxRuleCache::bustVat($model->vat_version_id);
        }
        if ($model instanceof CorporateTaxVersion) {
            TaxRuleCache::bustCit($model->tax_jurisdiction_id, $model->tax_year);
        }
    }
}
