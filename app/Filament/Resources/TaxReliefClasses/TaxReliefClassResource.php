<?php

namespace App\Filament\Resources\TaxReliefClasses;

use App\Filament\Resources\TaxReliefClasses\Pages\CreateTaxReliefClass;
use App\Filament\Resources\TaxReliefClasses\Pages\EditTaxReliefClass;
use App\Filament\Resources\TaxReliefClasses\Pages\ListTaxReliefClasses;
use App\Filament\Resources\TaxReliefClasses\Schemas\TaxReliefClassForm;
use App\Filament\Resources\TaxReliefClasses\Tables\TaxReliefClassesTable;
use App\Models\TaxReliefClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxReliefClassResource extends Resource
{
    protected static ?string $model = TaxReliefClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaxReliefClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxReliefClassesTable::configure($table);
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
            'index' => ListTaxReliefClasses::route('/'),
            'create' => CreateTaxReliefClass::route('/create'),
            'edit' => EditTaxReliefClass::route('/{record}/edit'),
        ];
    }
}
