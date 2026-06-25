<?php

namespace App\Filament\Resources\Shareholders\Tables;

use App\Filament\Resources\Shareholders\ShareholderResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShareholdersTable
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
            ->recordUrl(fn ($record): string => ShareholderResource::getUrl('view', ['record' => $record]));
    }
}
