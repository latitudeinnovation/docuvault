<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentDocumentsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Documents';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->url(fn (Document $record): string => DocumentResource::getUrl('view', ['record' => $record])),
                TextColumn::make('owner.name')
                    ->label('Owner'),
                TextColumn::make('document_type')
                    ->label('Type'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
