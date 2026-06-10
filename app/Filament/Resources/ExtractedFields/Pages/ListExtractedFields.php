<?php

namespace App\Filament\Resources\ExtractedFields\Pages;

use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExtractedFields extends ListRecords
{
    protected static string $resource = ExtractedFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
