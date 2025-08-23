<?php

namespace App\Filament\Resources\WithholdingCredits\Pages;

use App\Filament\Resources\WithholdingCredits\WithholdingCreditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWithholdingCredit extends EditRecord
{
    protected static string $resource = WithholdingCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
