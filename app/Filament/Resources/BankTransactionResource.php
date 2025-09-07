<?php

namespace App\Filament\Resources;

use App\Models\BankTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankTransactionResource extends Resource
{
    protected static ?string $model = BankTransaction::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';
    protected static \UnitEnum|string|null $navigationGroup = 'Transactions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('description')->searchable(),
                Tables\Columns\TextColumn::make('counterparty')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('NGN')->sortable(),
                Tables\Columns\TextColumn::make('category_account_id')->label('Category'),
                Tables\Columns\TextColumn::make('tax_tag')->label('Tax Tag'),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'primary' => 'imported',
                    'success' => 'posted',
                    'warning' => 'locked',
                ]),
            ])
            ->filters([
                Tables\Filters\Filter::make('uncategorized')->query(fn($q) => $q->whereNull('category_account_id')),
                Tables\Filters\SelectFilter::make('status')->options([
                    'imported' => 'Imported', 'posted' => 'Posted', 'locked' => 'Locked'
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('categorize')
                    ->form([
                        Tables\Components\TextInput::make('category_account_id')->label('Category Account ID')->numeric()->required(),
                        Tables\Components\TextInput::make('tax_tag')->label('Tax Tag'),
                        Tables\Components\Toggle::make('lock')->label('Lock after categorize'),
                    ])
                    ->action(function (BankTransaction $record, array $data) {
                        $updates = ['category_account_id' => $data['category_account_id'], 'categorized_at' => now()];
                        if (!empty($data['tax_tag'])) $updates['tax_tag'] = $data['tax_tag'];
                        if (!empty($data['lock'])) $updates['status'] = 'locked';
                        $record->update($updates);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkCategorize')
                    ->label('Categorize Selected')
                    ->form([
                        Tables\Components\TextInput::make('category_account_id')->label('Category Account ID')->numeric()->required(),
                        Tables\Components\TextInput::make('tax_tag')->label('Tax Tag'),
                        Tables\Components\Toggle::make('lock')->label('Lock after categorize'),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $updates = ['category_account_id' => $data['category_account_id'], 'categorized_at' => now()];
                            if (!empty($data['tax_tag'])) $updates['tax_tag'] = $data['tax_tag'];
                            if (!empty($data['lock'])) $updates['status'] = 'locked';
                            $record->update($updates);
                        }
                    }),
            ]);
    }
}
