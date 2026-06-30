<?php

namespace App\Filament\Resources\DocumentTypes\Tables;

use App\Models\DocumentType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->badge()
                    ->color(fn (DocumentType $record): string => $record->color)
                    ->icon(fn (DocumentType $record): string => $record->icon)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('color')
                    ->badge()
                    ->color(fn (string $state): string => $state),
                TextColumn::make('icon')
                    ->color('gray'),
                IconColumn::make('is_system')
                    ->label('Built-in')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn (DocumentType $record): bool => ! $record->is_system),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
