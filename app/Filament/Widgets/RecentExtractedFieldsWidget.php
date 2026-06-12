<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use App\Models\ExtractedField;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentExtractedFieldsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Extracted Fields';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ExtractedField::query()->with('document')->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('document.title')
                    ->label('Document')
                    ->searchable(),
                TextColumn::make('field_label')
                    ->label('Field'),
                TextColumn::make('value')
                    ->limit(50),
                TextColumn::make('confidence')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Extracted')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
