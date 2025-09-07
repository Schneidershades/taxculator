<?php

namespace App\Providers\Filament;

use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages;
use Filament\Widgets;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login() // use default auth
            ->brandName('TaxPilot Admin')
            ->colors([
                'primary' => '#2D5BFF',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\AdminStatsOverview::class,
                \App\Filament\Widgets\RecentIngestionJobs::class,
                \App\Filament\Widgets\RecentTransactions::class,
            ]);
    }
}

