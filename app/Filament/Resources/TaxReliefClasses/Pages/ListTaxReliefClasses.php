<?php

namespace App\Filament\Resources\TaxReliefClasses\Pages;

use App\Filament\Resources\TaxReliefClasses\TaxReliefClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxReliefClasses extends ListRecords
{
    protected static string $resource = TaxReliefClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
