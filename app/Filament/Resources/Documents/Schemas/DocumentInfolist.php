<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Document;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('owner.name')
                            ->label('Owner'),
                        TextEntry::make('document_type')
                            ->label('Document type'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('original_file_name')
                            ->label('Original file'),
                        TextEntry::make('file_type')
                            ->label('MIME type'),
                        TextEntry::make('ai_confidence')
                            ->label('AI confidence')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('processed_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Raw AI result')
                    ->schema([
                        TextEntry::make('ai_raw_json')
                            ->label('Payload')
                            ->getStateUsing(fn (Document $record): ?string => self::formatRawAiResult($record->ai_raw_json))
                            ->fontFamily(FontFamily::Mono)
                            ->wrap()
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    private static function formatRawAiResult(mixed $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        if (! is_array($payload)) {
            return (string) $payload;
        }

        return json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ) ?: null;
    }
}
