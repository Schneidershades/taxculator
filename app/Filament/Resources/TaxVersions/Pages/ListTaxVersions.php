<?php

namespace App\Filament\Resources\TaxVersions\Pages;

use App\Filament\Resources\TaxVersions\TaxVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxVersions extends ListRecords
{
    protected static string $resource = TaxVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
