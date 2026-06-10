<?php

namespace App\Filament\Resources\ExtractedFields\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExtractedFieldInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Extracted field')
                    ->schema([
                        TextEntry::make('document.title'),
                        TextEntry::make('field_key'),
                        TextEntry::make('field_label'),
                        TextEntry::make('confidence')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('value')
                            ->columnSpanFull(),
                        TextEntry::make('corrected_value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
