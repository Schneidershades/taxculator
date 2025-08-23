<?php

namespace App\Filament\Resources\TaxVersions;

use App\Filament\Resources\TaxVersions\Pages\CreateTaxVersion;
use App\Filament\Resources\TaxVersions\Pages\EditTaxVersion;
use App\Filament\Resources\TaxVersions\Pages\ListTaxVersions;
use App\Filament\Resources\TaxVersions\Pages\ViewTaxVersion;
use App\Filament\Resources\TaxVersions\Schemas\TaxVersionForm;
use App\Filament\Resources\TaxVersions\Schemas\TaxVersionInfolist;
use App\Filament\Resources\TaxVersions\Tables\TaxVersionsTable;
use App\Models\TaxVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxVersionResource extends Resource
{
    protected static ?string $model = TaxVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tax_year';

    public static function form(Schema $schema): Schema
    {
        return TaxVersionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxVersionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\TaxVersions\RelationManagers\ClassLinksRelationManager::class,
            \App\Filament\Resources\TaxVersions\RelationManagers\DeductionRulesRelationManager::class,
            \App\Filament\Resources\TaxVersions\RelationManagers\ReliefRulesRelationManager::class,
            \App\Filament\Resources\TaxVersions\RelationManagers\TariffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxVersions::route('/'),
            'create' => CreateTaxVersion::route('/create'),
            'view' => ViewTaxVersion::route('/{record}'),
            'edit' => EditTaxVersion::route('/{record}/edit'),
        ];
    }
}
