<?php

namespace App\Filament\Resources\TaxVersions\Pages;

use App\Models\TaxVersion;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\TaxVersions\TaxVersionResource;

class EditTaxVersion extends EditRecord
{
    protected static string $resource = TaxVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // … keep your existing actions (e.g., Clone Version, Export/Import)

            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-check-circle')
                ->visible(fn(TaxVersion $record) => $record->status === TaxVersion::STATUS_DRAFT)
                ->requiresConfirmation()
                ->action(function (TaxVersion $record) {
                    $record->publish();
                    Notification::make()->success()->title('Version published.')->send();
                }),

            Action::make('freeze')
                ->label('Freeze')
                ->icon('heroicon-o-snowflake')
                ->visible(fn(TaxVersion $record) => in_array($record->status, [TaxVersion::STATUS_PUBLISHED], true))
                ->requiresConfirmation()
                ->action(function (TaxVersion $record) {
                    $record->freeze();
                    Notification::make()->success()->title('Version frozen.')->send();
                }),

            Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn(TaxVersion $record) => in_array($record->status, [TaxVersion::STATUS_PUBLISHED, TaxVersion::STATUS_FROZEN], true))
                ->requiresConfirmation()
                ->action(function (TaxVersion $record) {
                    $record->unpublish();
                    Notification::make()->success()->title('Version moved back to Draft.')->send();
                }),

            Action::make('archive')
                ->label('Archive')
                ->icon('heroicon-o-archive-box')
                ->color('danger')
                ->visible(fn(TaxVersion $record) => $record->status !== TaxVersion::STATUS_ARCHIVED)
                ->requiresConfirmation()
                ->action(function (TaxVersion $record) {
                    $record->archive();
                    Notification::make()->success()->title('Version archived.')->send();
                }),
        ];
    }
}
