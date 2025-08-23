<?php

namespace App\Filament\Resources\TaxTransactions;

use App\Filament\Resources\TaxTransactions\Pages\CreateTaxTransaction;
use App\Filament\Resources\TaxTransactions\Pages\EditTaxTransaction;
use App\Filament\Resources\TaxTransactions\Pages\ListTaxTransactions;
use App\Filament\Resources\TaxTransactions\Pages\ViewTaxTransaction;
use App\Filament\Resources\TaxTransactions\Schemas\TaxTransactionForm;
use App\Filament\Resources\TaxTransactions\Schemas\TaxTransactionInfolist;
use App\Filament\Resources\TaxTransactions\Tables\TaxTransactionsTable;
use App\Models\TaxTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxTransactionResource extends Resource
{
    protected static ?string $model = TaxTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'identifier';

    public static function form(Schema $schema): Schema
    {
        return TaxTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxTransactions::route('/'),
            'create' => CreateTaxTransaction::route('/create'),
            'view' => ViewTaxTransaction::route('/{record}'),
            'edit' => EditTaxTransaction::route('/{record}/edit'),
        ];
    }
}
