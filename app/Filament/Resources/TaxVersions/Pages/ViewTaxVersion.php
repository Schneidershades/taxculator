<?php

namespace App\Filament\Resources\TaxVersions\Pages;

use App\Filament\Resources\TaxVersions\TaxVersionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxVersion extends ViewRecord
{
    protected static string $resource = TaxVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
