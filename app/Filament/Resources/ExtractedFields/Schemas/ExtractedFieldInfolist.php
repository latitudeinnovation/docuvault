<?php

namespace App\Filament\Resources\ExtractedFields\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class ExtractedFieldInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('document.title')
                                    ->label('Document')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->icon('heroicon-o-document-text'),
                            TextEntry::make('status')
                                ->badge()
                                ->size(TextSize::Large),
                        ]),
                    ]),

                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Field Details')
                            ->description('Metadata about this extracted field')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                TextEntry::make('field_key')
                                    ->label('Field Key')
                                    ->fontFamily(FontFamily::Mono)
                                    ->badge()
                                    ->color('gray')
                                    ->copyable(),
                                TextEntry::make('field_label')
                                    ->label('Field Name')
                                    ->columnSpan(2),
                                TextEntry::make('created_at')
                                    ->label('Extracted At')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Section::make('AI Confidence')
                            ->description('How confident the AI is in this value')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                TextEntry::make('confidence')
                                    ->hiddenLabel()
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->color(fn ($state): string => match (true) {
                                        $state === null => 'gray',
                                        (float) $state >= 0.8 => 'success',
                                        (float) $state >= 0.5 => 'warning',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn ($state): string => $state !== null
                                        ? number_format((float) $state * 100, 1) . '%'
                                        : 'Not available'),
                            ]),
                    ]),

                Section::make('Extracted Value')
                    ->description('The raw value returned by the AI')
                    ->icon('heroicon-o-cpu-chip')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('value')
                            ->hiddenLabel()
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->placeholder('No value extracted')
                            ->columnSpanFull(),
                    ]),

                Section::make('Corrected Value')
                    ->description('Human-reviewed and corrected value')
                    ->icon('heroicon-o-pencil-square')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('corrected_value')
                            ->hiddenLabel()
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->placeholder('No correction made')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
