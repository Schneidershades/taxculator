<?php

namespace App\Filament\Resources\TaxReliefClasses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaxReliefClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('code'),
                TextInput::make('short_name'),
                TextInput::make('slug'),
                TextInput::make('type'),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
