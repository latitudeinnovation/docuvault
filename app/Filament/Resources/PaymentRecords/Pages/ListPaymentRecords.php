<?php

namespace App\Filament\Resources\PaymentRecords\Pages;

use App\Filament\Resources\PaymentRecords\PaymentRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListPaymentRecords extends ListRecords
{
    protected static string $resource = PaymentRecordResource::class;

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
            'in' => Tab::make('In')
                ->modifyQueryUsing(fn ($query) => $query->where('transaction_type', 'in')),
            'out' => Tab::make('Out')
                ->modifyQueryUsing(fn ($query) => $query->where('transaction_type', 'out')),
        ];
    }
}
