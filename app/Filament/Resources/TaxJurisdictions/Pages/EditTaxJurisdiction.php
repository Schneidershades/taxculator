<?php

namespace App\Filament\Resources\TaxJurisdictions\Pages;

use App\Filament\Resources\TaxJurisdictions\TaxJurisdictionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxJurisdiction extends EditRecord
{
    protected static string $resource = TaxJurisdictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
