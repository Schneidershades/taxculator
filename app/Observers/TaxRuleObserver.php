<?php

namespace App\Observers;

use App\Support\TaxRuleCache;

trait TaxRuleObserver
{
    public function saved($model): void
    {
        $this->flush($model);
    }

    public function deleted($model): void
    {
        $this->flush($model);
    }

    private function flush($model): void
    {
        $versionId = $model->tax_version_id ?? null;
        if ($versionId) {
            TaxRuleCache::flushForVersion((int) $versionId);
        }
    }
}
