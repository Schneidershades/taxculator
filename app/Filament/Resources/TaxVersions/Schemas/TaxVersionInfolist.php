<?php

namespace App\Filament\Resources\TaxVersions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaxVersionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tax_jurisdiction_id')
                    ->numeric(),
                TextEntry::make('tax_year')
                    ->numeric(),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
