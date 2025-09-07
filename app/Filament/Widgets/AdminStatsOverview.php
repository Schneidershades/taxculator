<?php

namespace App\Filament\Widgets;

use App\Models\BankTransaction;
use App\Models\IngestionJob;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class AdminStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $tenants = Tenant::count();
        $txTotal = BankTransaction::count();
        $uncat = BankTransaction::whereNull('category_account_id')->count();
        $pending = IngestionJob::whereIn('status', ['queued','processing'])->count();

        return [
            Card::make('Tenants', $tenants),
            Card::make('Bank Transactions', number_format($txTotal))
                ->description('Total imported')
                ->descriptionIcon('heroicon-o-banknotes'),
            Card::make('Uncategorized', number_format($uncat))
                ->description('Needs attention')
                ->descriptionIcon('heroicon-o-exclamation-triangle'),
            Card::make('Pending Imports', number_format($pending))
                ->description('Queued or Processing')
                ->descriptionIcon('heroicon-o-arrow-down-tray'),
        ];
    }
}

