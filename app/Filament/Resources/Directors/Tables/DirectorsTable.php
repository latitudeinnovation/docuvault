<?php

namespace App\Filament\Resources\Directors\Tables;

use App\Filament\Resources\Directors\DirectorResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DirectorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('companies'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ic_passport')
                    ->label('IC / Passport')
                    ->searchable(),
                TextColumn::make('companies_count')
                    ->label('Companies')
                    ->badge()
                    ->sortable(),
            ])
            ->recordUrl(fn ($record): string => DirectorResource::getUrl('view', ['record' => $record]));
    }
}
