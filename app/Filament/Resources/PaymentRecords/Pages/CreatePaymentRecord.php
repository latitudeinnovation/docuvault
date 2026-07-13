<?php

namespace App\Filament\Resources\PaymentRecords\Pages;

use App\Filament\Resources\PaymentRecords\PaymentRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentRecord extends CreateRecord
{
    protected static string $resource = PaymentRecordResource::class;
}
