<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

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
            ...collect(DocumentStatus::cases())->mapWithKeys(fn (DocumentStatus $status) => [
                $status->value => Tab::make($status->getLabel())
                    ->icon($status->getIcon())
                    ->modifyQueryUsing(fn ($query) => $query->where('status', $status->value))
                    ->badge(Document::where('status', $status->value)->count()),
            ])->all(),
        ];
    }
}
