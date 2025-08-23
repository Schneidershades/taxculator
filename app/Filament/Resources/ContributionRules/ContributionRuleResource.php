<?php

namespace App\Filament\Resources\ContributionRules;

use App\Filament\Resources\ContributionRules\Pages\CreateContributionRule;
use App\Filament\Resources\ContributionRules\Pages\EditContributionRule;
use App\Filament\Resources\ContributionRules\Pages\ListContributionRules;
use App\Filament\Resources\ContributionRules\Schemas\ContributionRuleForm;
use App\Filament\Resources\ContributionRules\Tables\ContributionRulesTable;
use App\Models\ContributionRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContributionRuleResource extends Resource
{
    protected static ?string $model = ContributionRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ContributionRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributionRulesTable::configure($table);
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
            'index' => ListContributionRules::route('/'),
            'create' => CreateContributionRule::route('/create'),
            'edit' => EditContributionRule::route('/{record}/edit'),
        ];
    }
}
