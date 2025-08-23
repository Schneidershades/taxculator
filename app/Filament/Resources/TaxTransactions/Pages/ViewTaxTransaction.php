<?php

namespace App\Filament\Resources\TaxTransactions\Pages;

use App\Filament\Resources\TaxTransactions\TaxTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxTransaction extends ViewRecord
{
    protected static string $resource = TaxTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
