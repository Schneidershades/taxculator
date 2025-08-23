<?php

namespace App\Filament\Resources\TaxDeductionClasses\Pages;

use App\Filament\Resources\TaxDeductionClasses\TaxDeductionClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxDeductionClasses extends ListRecords
{
    protected static string $resource = TaxDeductionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
