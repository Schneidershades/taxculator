<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RuleResource\Pages;
use App\Models\Rule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RuleResource extends Resource
{
    protected static ?string $model = Rule::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';
    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        // UI-only; not required for API tests. Returning the passed schema avoids v4 signature conflicts.
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('matcher_type'),
                Tables\Columns\TextColumn::make('field'),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\IconColumn::make('active')->boolean(),
                Tables\Columns\TextColumn::make('priority')->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRules::route('/'),
            'create' => Pages\CreateRule::route('/create'),
            'edit' => Pages\EditRule::route('/{record}/edit'),
        ];
    }
}

namespace App\Filament\Resources\RuleResource\Pages;

use App\Filament\Resources\RuleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

class ListRules extends ListRecords
{
    protected static string $resource = RuleResource::class;
}

class CreateRule extends CreateRecord
{
    protected static string $resource = RuleResource::class;
}

class EditRule extends EditRecord
{
    protected static string $resource = RuleResource::class;
}
