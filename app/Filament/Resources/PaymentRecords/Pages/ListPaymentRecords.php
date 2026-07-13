<?php

namespace App\Filament\Resources\PaymentRecords\Pages;

use App\Filament\Resources\PaymentRecords\PaymentRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentRecords extends ListRecords
{
    protected static string $resource = PaymentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
