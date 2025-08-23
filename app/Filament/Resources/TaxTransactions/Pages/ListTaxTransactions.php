<?php

namespace App\Filament\Resources\TaxTransactions\Pages;

use App\Filament\Resources\TaxTransactions\TaxTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxTransactions extends ListRecords
{
    protected static string $resource = TaxTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
