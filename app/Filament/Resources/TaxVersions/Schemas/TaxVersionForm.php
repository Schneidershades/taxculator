<?php

namespace App\Filament\Resources\TaxVersions\Schemas;

use App\Models\TaxVersion;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;

class TaxVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tax_jurisdiction_id')
                ->required()
                ->numeric()
                ->live()
                ->disabled(fn(?TaxVersion $record) => $record?->status === TaxVersion::STATUS_FROZEN),

            Select::make('status')
                ->options([
                    TaxVersion::STATUS_DRAFT     => 'Draft',
                    TaxVersion::STATUS_PUBLISHED => 'Published',
                    TaxVersion::STATUS_FROZEN    => 'Frozen',
                    TaxVersion::STATUS_ARCHIVED  => 'Archived',
                ])
                ->default(TaxVersion::STATUS_DRAFT)
                ->required()
                ->disabled(fn(?TaxVersion $record) => $record?->status === TaxVersion::STATUS_FROZEN),

            TextInput::make('tax_year')
                ->required()
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100)
                ->live()
                ->disabled(fn(?TaxVersion $record) => $record?->status === TaxVersion::STATUS_FROZEN)
                ->rule(function (Get $get, ?TaxVersion $record) {
                    $jurId = (int) $get('tax_jurisdiction_id');
                    if (!$jurId) return null;
                    return Rule::unique('tax_versions', 'tax_year')
                        ->where(fn($q) => $q->where('tax_jurisdiction_id', $jurId))
                        ->ignore($record?->id);
                })
                ->validationMessages(['unique' => 'This jurisdiction already has this tax year.']),

            DatePicker::make('effective_from')
                ->required()
                ->disabled(fn(?TaxVersion $record) => $record?->status === TaxVersion::STATUS_FROZEN),

            DatePicker::make('effective_to')
                ->disabled(fn(?TaxVersion $record) => $record?->status === TaxVersion::STATUS_FROZEN),
        ]);
    }
}
