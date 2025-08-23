<?php

namespace App\Filament\Resources\TaxTransactions\Pages;

use App\Filament\Resources\TaxTransactions\TaxTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxTransaction extends EditRecord
{
    protected static string $resource = TaxTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
