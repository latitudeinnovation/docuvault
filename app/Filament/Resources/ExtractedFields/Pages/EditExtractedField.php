<?php

namespace App\Filament\Resources\ExtractedFields\Pages;

use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExtractedField extends EditRecord
{
    protected static string $resource = ExtractedFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
