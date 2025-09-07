<?php

namespace App\Filament\Widgets;

use App\Models\BankTransaction;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecentTransactions extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return BankTransaction::query()->latest('posted_at')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('posted_at')->date(),
            Tables\Columns\TextColumn::make('description')->searchable(),
            Tables\Columns\TextColumn::make('amount')->money('NGN'),
            Tables\Columns\TextColumn::make('tax_tag')->label('Tax'),
            Tables\Columns\TextColumn::make('status'),
        ];
    }
}
