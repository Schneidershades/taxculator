<?php

namespace App\Filament\Resources\TaxReliefClasses\Pages;

use App\Filament\Resources\TaxReliefClasses\TaxReliefClassResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxReliefClass extends EditRecord
{
    protected static string $resource = TaxReliefClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
