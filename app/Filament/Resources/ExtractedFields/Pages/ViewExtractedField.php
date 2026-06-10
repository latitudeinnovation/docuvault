<?php

namespace App\Filament\Resources\ExtractedFields\Pages;

use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExtractedField extends ViewRecord
{
    protected static string $resource = ExtractedFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
