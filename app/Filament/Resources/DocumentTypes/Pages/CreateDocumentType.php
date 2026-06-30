<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Models\DocumentType;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateDocumentType extends CreateRecord
{
    protected static string $resource = DocumentTypeResource::class;

    /**
     * Derive the stored `key` from the label, kept unique so it can be assigned
     * silently without the admin ever filling it in.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $base = Str::slug((string) ($data['label'] ?? ''), '_') ?: 'type';

        $key = $base;
        $suffix = 2;

        while (DocumentType::where('key', $key)->exists()) {
            $key = $base.'_'.$suffix++;
        }

        $data['key'] = $key;
        $data['icon'] = DocumentType::iconForLabel((string) ($data['label'] ?? ''));

        return $data;
    }
}
