<?php

namespace App\Filament\Resources\TaxClasses;

use App\Filament\Resources\TaxClasses\Pages\CreateTaxClass;
use App\Filament\Resources\TaxClasses\Pages\EditTaxClass;
use App\Filament\Resources\TaxClasses\Pages\ListTaxClasses;
use App\Filament\Resources\TaxClasses\Schemas\TaxClassForm;
use App\Filament\Resources\TaxClasses\Tables\TaxClassesTable;
use App\Models\TaxClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxClassResource extends Resource
{
    protected static ?string $model = TaxClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaxClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxClassesTable::configure($table);
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
            'index' => ListTaxClasses::route('/'),
            'create' => CreateTaxClass::route('/create'),
            'edit' => EditTaxClass::route('/{record}/edit'),
        ];
    }
}
