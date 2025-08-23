<?php

namespace App\Filament\Resources\TaxVersions\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TariffsRelationManager extends RelationManager
{
    protected static string $relationship = 'tariffs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bracket_min')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bracket_max')
                    ->numeric(),
                TextInput::make('rate_type')
                    ->required(),
                TextInput::make('rate_value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('ordering')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tax_version_id')
            ->columns([
                TextColumn::make('bracket_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bracket_max')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_type')
                    ->searchable(),
                TextColumn::make('rate_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ordering')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
