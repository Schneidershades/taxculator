<?php

namespace App\Filament\Resources\TaxDeductionClasses;

use App\Filament\Resources\TaxDeductionClasses\Pages\CreateTaxDeductionClass;
use App\Filament\Resources\TaxDeductionClasses\Pages\EditTaxDeductionClass;
use App\Filament\Resources\TaxDeductionClasses\Pages\ListTaxDeductionClasses;
use App\Filament\Resources\TaxDeductionClasses\Schemas\TaxDeductionClassForm;
use App\Filament\Resources\TaxDeductionClasses\Tables\TaxDeductionClassesTable;
use App\Models\TaxDeductionClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxDeductionClassResource extends Resource
{
    protected static ?string $model = TaxDeductionClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaxDeductionClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxDeductionClassesTable::configure($table);
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
            'index' => ListTaxDeductionClasses::route('/'),
            'create' => CreateTaxDeductionClass::route('/create'),
            'edit' => EditTaxDeductionClass::route('/{record}/edit'),
        ];
    }
}
