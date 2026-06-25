<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Support\JsonPresenter;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

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
                        TextEntry::make('failure_reason')
                            ->label('Failure reason')
                            ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Failed)
                            ->color('danger')
                            ->icon(Heroicon::ExclamationTriangle)
                            ->columnSpanFull(),
                        TextEntry::make('original_file_name')
                            ->label('Original file'),
                        TextEntry::make('file_type')
                            ->label('MIME type'),
                        TextEntry::make('ai_confidence')
                            ->label('Confidence')
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
                            ->modalContent(fn (Document $record): View => view('filament.documents.json-viewer', [
                                'blocks' => [
                                    [
                                        'label' => 'Extracted Result',
                                        'plain' => JsonPresenter::pretty(JsonPresenter::normalized($record)),
                                        'html' => JsonPresenter::html(JsonPresenter::normalized($record)),
                                    ],
                                    [
                                        'label' => 'Raw AI Result',
                                        'plain' => JsonPresenter::pretty($record->ai_raw_json),
                                        'html' => JsonPresenter::html($record->ai_raw_json),
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
                                            ->getStateUsing(fn (Document $record): ?string => JsonPresenter::compact(JsonPresenter::normalized($record)))
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
                                            ->getStateUsing(fn (Document $record): ?string => JsonPresenter::compact($record->ai_raw_json))
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
}
