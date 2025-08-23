<?php

namespace App\Filament\Resources\TaxJurisdictions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxJurisdictionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('parent_id')
                    ->numeric(),
                TextInput::make('level')
                    ->required(),
                TextInput::make('country_code'),
                TextInput::make('state_code'),
                TextInput::make('local_code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('currency_code'),
            ]);
    }
}
