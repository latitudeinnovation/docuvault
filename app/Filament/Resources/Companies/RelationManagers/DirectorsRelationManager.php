<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DirectorsRelationManager extends RelationManager
{
    protected static string $relationship = 'directors';

    protected static ?string $title = 'Directors';

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
                TextColumn::make('pivot.designation')
                    ->label('Designation')
                    ->placeholder('—'),
                TextColumn::make('pivot.appointed_at')
                    ->label('Appointed')
                    ->date()
                    ->placeholder('—'),
            ]);
    }
}
