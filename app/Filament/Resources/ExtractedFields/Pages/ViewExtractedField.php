<?php

namespace App\Filament\Resources\ExtractedFields\Pages;

use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use App\Models\ExtractedField;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewExtractedField extends ViewRecord
{
    protected static string $resource = ExtractedFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (ExtractedField $record): bool => $record->status !== ExtractedFieldStatus::Approved)
                ->action(function (ExtractedField $record): void {
                    $record->update(['status' => ExtractedFieldStatus::Approved]);

                    Notification::make()
                        ->success()
                        ->title('Field approved')
                        ->send();
                }),
            Action::make('reject')
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (ExtractedField $record): bool => $record->status !== ExtractedFieldStatus::Rejected)
                ->action(function (ExtractedField $record): void {
                    $record->update(['status' => ExtractedFieldStatus::Rejected]);

                    Notification::make()
                        ->success()
                        ->title('Field rejected')
                        ->send();
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
