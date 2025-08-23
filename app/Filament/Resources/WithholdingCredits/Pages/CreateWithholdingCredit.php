<?php

namespace App\Filament\Resources\WithholdingCredits\Pages;

use App\Filament\Resources\WithholdingCredits\WithholdingCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWithholdingCredit extends CreateRecord
{
    protected static string $resource = WithholdingCreditResource::class;
}
