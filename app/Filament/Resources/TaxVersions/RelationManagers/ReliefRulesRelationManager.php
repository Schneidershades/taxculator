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

class ReliefRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'reliefRules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tax_relief_class_id')
                    ->required()
                    ->numeric(),
                TextInput::make('relief_type')
                    ->required(),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('minimum_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('maximum_amount')
                    ->numeric(),
                TextInput::make('minimum_status')
                    ->required()
                    ->default('static'),
                TextInput::make('maximum_status')
                    ->required()
                    ->default('unlimited'),
                TextInput::make('combine_mode')
                    ->required()
                    ->default('stack'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tax_version_id')
            ->columns([
                TextColumn::make('tax_relief_class_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('relief_type')
                    ->searchable(),
                TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('minimum_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('maximum_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('minimum_status')
                    ->searchable(),
                TextColumn::make('maximum_status')
                    ->searchable(),
                TextColumn::make('combine_mode')
                    ->searchable(),
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
