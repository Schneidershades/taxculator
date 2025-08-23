<?php

namespace App\Filament\Resources\WithholdingCredits;

use App\Filament\Resources\WithholdingCredits\Pages\CreateWithholdingCredit;
use App\Filament\Resources\WithholdingCredits\Pages\EditWithholdingCredit;
use App\Filament\Resources\WithholdingCredits\Pages\ListWithholdingCredits;
use App\Filament\Resources\WithholdingCredits\Schemas\WithholdingCreditForm;
use App\Filament\Resources\WithholdingCredits\Tables\WithholdingCreditsTable;
use App\Models\WithholdingCredit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WithholdingCreditResource extends Resource
{
    protected static ?string $model = WithholdingCredit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WithholdingCreditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WithholdingCreditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithholdingCredits::route('/'),
            'create' => CreateWithholdingCredit::route('/create'),
            'edit' => EditWithholdingCredit::route('/{record}/edit'),
        ];
    }
}
