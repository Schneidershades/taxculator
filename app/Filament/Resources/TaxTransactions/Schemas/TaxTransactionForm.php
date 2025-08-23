<?php

namespace App\Filament\Resources\TaxTransactions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaxTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('identifier'),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('idempotency_key'),
                Textarea::make('input_snapshot')
                    ->columnSpanFull(),
                Textarea::make('versions_snapshot')
                    ->columnSpanFull(),
                TextInput::make('rules_hash'),
                Textarea::make('statement')
                    ->columnSpanFull(),
            ]);
    }
}
