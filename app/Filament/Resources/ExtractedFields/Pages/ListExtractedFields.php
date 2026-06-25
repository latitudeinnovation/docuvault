<?php

namespace App\Filament\Resources\ExtractedFields\Pages;

use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\ExtractedFields\ExtractedFieldResource;
use App\Models\ExtractedField;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListExtractedFields extends ListRecords
{
    protected static string $resource = ExtractedFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            ...collect(ExtractedFieldStatus::cases())->mapWithKeys(fn (ExtractedFieldStatus $status) => [
                $status->value => Tab::make($status->getLabel())
                    ->icon($status->getIcon())
                    ->modifyQueryUsing(fn ($query) => $query->where('status', $status->value))
                    ->badge(ExtractedField::where('status', $status->value)->count()),
            ])->all(),
        ];
    }
}
