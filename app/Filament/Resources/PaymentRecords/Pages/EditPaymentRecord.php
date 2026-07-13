<?php

namespace App\Filament\Resources\PaymentRecords\Pages;

use App\Filament\Resources\PaymentRecords\PaymentRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentRecord extends EditRecord
{
    protected static string $resource = PaymentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
