<?php

namespace App\Filament\Resources\WithholdingCredits\Pages;

use App\Filament\Resources\WithholdingCredits\WithholdingCreditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWithholdingCredits extends ListRecords
{
    protected static string $resource = WithholdingCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
