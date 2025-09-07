<?php

namespace App\Filament\Widgets;

use App\Models\IngestionJob;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecentIngestionJobs extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return IngestionJob::query()->latest()->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'primary' => 'queued',
                'warning' => 'processing',
                'success' => 'completed',
                'danger' => 'failed',
            ]),
            Tables\Columns\TextColumn::make('created_count')->label('Created'),
            Tables\Columns\TextColumn::make('duplicates_count')->label('Duplicates'),
            Tables\Columns\TextColumn::make('errors_count')->label('Errors')->color('danger'),
            Tables\Columns\TextColumn::make('created_at')->since(),
        ];
    }
}
