<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->color('primary'),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Document) {
            return;
        }

        $mimeType = Storage::disk($record->file_disk)->mimeType($record->file_path);

        if ($mimeType && $mimeType !== $record->file_type) {
            $record->forceFill(['file_type' => $mimeType])->save();
        }
    }
}
