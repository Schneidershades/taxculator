<?php

namespace App\Filament\Resources\ContributionRules\Pages;

use App\Filament\Resources\ContributionRules\ContributionRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContributionRules extends ListRecords
{
    protected static string $resource = ContributionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
