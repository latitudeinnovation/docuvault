<?php

namespace App\Filament\Resources\PaymentRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'In',
                        'out' => 'Out',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Main Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bank_account')
                    ->label('Bank Account')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('remarks')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ]),
                SelectFilter::make('company_id')
                    ->label('Main Account')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
