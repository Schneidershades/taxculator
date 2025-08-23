<?php

namespace App\Filament\Resources\TaxTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaxTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('identifier'),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('idempotency_key'),
                TextEntry::make('rules_hash'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
