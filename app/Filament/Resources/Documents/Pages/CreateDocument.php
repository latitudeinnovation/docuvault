<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id();
        $data['file_disk'] = config('docuvault.documents.disk');
        $data['file_type'] ??= 'application/octet-stream';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Document) {
            return;
        }

        $mimeType = Storage::disk($record->file_disk)->mimeType($record->file_path);

        $record->forceFill([
            'file_type' => $mimeType ?: $record->file_type,
        ])->save();

        ProcessDocumentWithRaraxuan::dispatch($record);
    }
}
