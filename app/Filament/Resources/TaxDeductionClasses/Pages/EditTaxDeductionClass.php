<?php

namespace App\Filament\Resources\TaxDeductionClasses\Pages;

use App\Filament\Resources\TaxDeductionClasses\TaxDeductionClassResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxDeductionClass extends EditRecord
{
    protected static string $resource = TaxDeductionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
