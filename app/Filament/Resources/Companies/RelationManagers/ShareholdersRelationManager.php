<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShareholdersRelationManager extends RelationManager
{
    protected static string $relationship = 'shareholders';

    protected static ?string $title = 'Shareholders';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ic_passport')
                    ->label('IC / Passport')
                    ->placeholder('—'),
                TextColumn::make('pivot.shares')
                    ->label('Shares')
                    ->placeholder('—'),
            ]);
    }
}
