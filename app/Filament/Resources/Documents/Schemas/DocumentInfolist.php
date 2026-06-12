<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

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
                            ->label('Completeness')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('processed_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('JSON Format')
                    ->headerActions([
                        Action::make('viewJson')
                            ->label('View JSON')
                            ->icon(Heroicon::ArrowsPointingOut)
                            ->color('gray')
                            ->slideOver()
                            ->modalHeading('JSON result')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(fn(Document $record): View => view('filament.documents.json-viewer', [
                                'blocks' => [
                                    [
                                        'label' => 'Extracted Result',
                                        'plain' => self::prettyJson(self::normalizedPayload($record)),
                                        'html' => self::jsonHtml(self::normalizedPayload($record)),
                                    ],
                                    [
                                        'label' => 'Raw AI Result',
                                        'plain' => self::prettyJson($record->ai_raw_json),
                                        'html' => self::jsonHtml($record->ai_raw_json),
                                    ],
                                ],
                            ])),
                    ])
                    ->schema([
                        Tabs::make('Result')
                            ->tabs([
                                Tab::make('Extracted Result')
                                    ->schema([
                                        TextEntry::make('ai_raw_json')
                                            ->hiddenLabel()
                                            ->getStateUsing(fn(Document $record): ?string => self::compactJson(self::normalizedPayload($record)))
                                            ->fontFamily(FontFamily::Mono)
                                            ->copyable()
                                            ->copyMessage('Copied JSON')
                                            ->wrap()
                                            ->extraAttributes(['style' => 'display:block;height:300px;overflow-y:auto'])
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Raw AI Result')
                                    ->schema([
                                        TextEntry::make('ai_raw_json')
                                            ->hiddenLabel()
                                            ->getStateUsing(fn(Document $record): ?string => self::compactJson($record->ai_raw_json))
                                            ->fontFamily(FontFamily::Mono)
                                            ->copyable()
                                            ->copyMessage('Copied JSON')
                                            ->wrap()
                                            ->extraAttributes(['style' => 'display:block;height:300px;overflow-y:auto'])
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * The decoded extraction result (normalized_result when present, else the envelope).
     */
    private static function normalizedPayload(Document $record): mixed
    {
        $payload = $record->ai_raw_json;

        if (!\is_array($payload)) {
            return $payload;
        }

        return $payload['normalized_result'] ?? $payload;
    }

    /**
     * Pretty-print a payload, deep-decoding any nested JSON-encoded string values
     * (e.g. the Raraxuan `data.result` blob) so the output is clean JSON, not an
     * escaped \n wall.
     */
    private static function prettyJson(mixed $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        if (!\is_array($payload)) {
            return (string) $payload;
        }

        return json_encode(
            self::deepDecode($payload),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ) ?: null;
    }

    /**
     * Syntax-highlighted, pretty JSON as safe HTML. Keys, string values, numbers,
     * booleans and null each get their own colour; string values (the important
     * extracted data) are emphasised. Used by the slide-over viewer.
     */
    private static function jsonHtml(mixed $payload): ?HtmlString
    {
        if (blank($payload)) {
            return null;
        }

        return new HtmlString(self::renderJsonNode(self::deepDecode($payload), 0));
    }

    private static function renderJsonNode(mixed $value, int $depth): string
    {
        $pad = str_repeat('    ', $depth);
        $padInner = str_repeat('    ', $depth + 1);

        if (\is_array($value)) {
            if ($value === []) {
                return array_is_list($value) ? '[]' : '{}';
            }

            $isList = array_is_list($value);
            [$open, $close] = $isList ? ['[', ']'] : ['{', '}'];

            $lines = [];

            foreach ($value as $key => $item) {
                $line = $padInner;

                if (!$isList) {
                    $line .= '<span style="color:#0284c7">"' . e((string) $key) . '"</span>'
                        . '<span style="color:#9ca3af">: </span>';
                }

                $line .= self::renderJsonNode($item, $depth + 1);
                $lines[] = $line;
            }

            return $open . "\n" . implode(",\n", $lines) . "\n" . $pad . $close;
        }

        if (\is_string($value)) {
            return '<span style="color:#047857;font-weight:600">"' . e($value) . '"</span>';
        }

        if (\is_bool($value)) {
            return '<span style="color:#9333ea">' . ($value ? 'true' : 'false') . '</span>';
        }

        if ($value === null) {
            return '<span style="color:#e11d48">null</span>';
        }

        return '<span style="color:#d97706">' . e((string) $value) . '</span>';
    }

    /**
     * Compact single-line JSON (still deep-decoded so there is no escaped \n wall).
     * Used for the inline preview; the slide-over uses prettyJson().
     */
    private static function compactJson(mixed $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        if (!\is_array($payload)) {
            return (string) $payload;
        }

        return json_encode(
            self::deepDecode($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ) ?: null;
    }

    /**
     * Recursively replace string values that are themselves JSON objects/arrays
     * with their decoded form.
     */
    private static function deepDecode(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map([self::class, 'deepDecode'], $value);
        }

        if (\is_string($value)) {
            $trimmed = ltrim($value);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                    return self::deepDecode($decoded);
                }
            }
        }

        return $value;
    }
}
