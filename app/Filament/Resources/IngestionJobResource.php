<?php

namespace App\Filament\Resources;

use App\Models\IngestionJob;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IngestionJobResource extends Resource
{
    protected static ?string $model = IngestionJob::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static \UnitEnum|string|null $navigationGroup = 'Imports';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'primary' => 'queued',
                    'warning' => 'processing',
                    'success' => 'completed',
                    'danger' => 'failed',
                ])->sortable(),
                Tables\Columns\TextColumn::make('created_count')->label('Created'),
                Tables\Columns\TextColumn::make('duplicates_count')->label('Duplicates'),
                Tables\Columns\TextColumn::make('errors_count')->label('Errors')->color('danger'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['queued' => 'Queued', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed'])
            ])
            ->actions([
            ])
            ->bulkActions([]);
    }
}
