<?php

namespace App\Filament\Resources\TaxJurisdictions;

use App\Filament\Resources\TaxJurisdictions\Pages\CreateTaxJurisdiction;
use App\Filament\Resources\TaxJurisdictions\Pages\EditTaxJurisdiction;
use App\Filament\Resources\TaxJurisdictions\Pages\ListTaxJurisdictions;
use App\Filament\Resources\TaxJurisdictions\Schemas\TaxJurisdictionForm;
use App\Filament\Resources\TaxJurisdictions\Tables\TaxJurisdictionsTable;
use App\Models\TaxJurisdiction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxJurisdictionResource extends Resource
{
    protected static ?string $model = TaxJurisdiction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaxJurisdictionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxJurisdictionsTable::configure($table);
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
            'index' => ListTaxJurisdictions::route('/'),
            'create' => CreateTaxJurisdiction::route('/create'),
            'edit' => EditTaxJurisdiction::route('/{record}/edit'),
        ];
    }
}
