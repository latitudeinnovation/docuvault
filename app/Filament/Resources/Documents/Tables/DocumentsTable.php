<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('ai_confidence')
                    ->label('AI confidence')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
                SelectFilter::make('document_type')
                    ->options(fn (): array => Document::query()
                        ->distinct()
                        ->orderBy('document_type')
                        ->pluck('document_type', 'document_type')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(fn (Document $record) => Storage::disk($record->file_disk)->download(
                        $record->file_path,
                        $record->original_file_name ?: basename($record->file_path),
                    )),
                Action::make('reprocess')
                    ->icon(Heroicon::ArrowPath)
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Document $record): void {
                        ProcessDocumentWithRaraxuan::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Document queued for processing')
                            ->send();
                    }),
                Action::make('approve')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Document $record): bool => $record->extractedFields()->exists()
                        && ! $record->extractedFields()
                            ->where('status', ExtractedFieldStatus::Pending->value)
                            ->exists())
                    ->action(function (Document $record): void {
                        $record->update(['status' => DocumentStatus::Approved]);

                        Notification::make()
                            ->success()
                            ->title('Document approved')
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
