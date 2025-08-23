<?php

namespace App\Filament\Resources\ContributionRules\Pages;

use App\Filament\Resources\ContributionRules\ContributionRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContributionRule extends EditRecord
{
    protected static string $resource = ContributionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
