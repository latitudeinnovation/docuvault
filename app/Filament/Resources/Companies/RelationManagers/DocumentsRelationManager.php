<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Enums\DocumentType;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultGroup('document_type')
            ->groups([
                Group::make('document_type')
                    ->label('Document type')
                    ->getTitleFromRecordUsing(fn ($record): string => DocumentType::fromValue($record->document_type)->getLabel()),
            ])
            ->defaultSort('processed_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DocumentType::fromValue($state)->getLabel())
                    ->color(fn (?string $state): string|array|null => DocumentType::fromValue($state)->getColor()),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('extracted_fields_count')
                    ->label('Fields')
                    ->counts('extractedFields')
                    ->badge(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn ($record): string => DocumentResource::getUrl('view', ['record' => $record]));
    }
}
