<?php

namespace App\Filament\Resources\TaxTransactions\Pages;

use App\Filament\Resources\TaxTransactions\TaxTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxTransaction extends CreateRecord
{
    protected static string $resource = TaxTransactionResource::class;
}
