<?php

namespace App\Filament\Resources\TaxClasses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('code'),
                TextInput::make('short_name'),
                TextInput::make('slug'),
            ]);
    }
}
