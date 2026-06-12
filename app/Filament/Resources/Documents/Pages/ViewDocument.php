<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->button()
                ->requiresConfirmation()
                ->visible(fn (Document $record): bool => $record->extractedFields()->exists()
                    && ! $record->extractedFields()->where('status', ExtractedFieldStatus::Pending->value)->exists()
                    && $record->status !== DocumentStatus::Approved)
                ->action(function (Document $record): void {
                    $record->update(['status' => DocumentStatus::Approved]);

                    Notification::make()
                        ->success()
                        ->title('Document approved')
                        ->send();
                }),
            Action::make('reprocess')
                ->icon(Heroicon::ArrowPath)
                ->color('info')
                ->button()
                ->requiresConfirmation()
                ->action(function (Document $record): void {
                    ProcessDocumentWithRaraxuan::dispatch($record);

                    Notification::make()
                        ->success()
                        ->title('Document queued for processing')
                        ->send();
                }),
            Action::make('download')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->button()
                ->action(fn (Document $record) => Storage::disk($record->file_disk)->download(
                    $record->file_path,
                    $record->original_file_name ?: basename($record->file_path),
                )),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
