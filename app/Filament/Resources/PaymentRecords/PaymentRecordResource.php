<?php

namespace App\Filament\Resources\PaymentRecords;

use App\Filament\Resources\PaymentRecords\Pages\CreatePaymentRecord;
use App\Filament\Resources\PaymentRecords\Pages\EditPaymentRecord;
use App\Filament\Resources\PaymentRecords\Pages\ListPaymentRecords;
use App\Filament\Resources\PaymentRecords\Schemas\PaymentRecordForm;
use App\Filament\Resources\PaymentRecords\Tables\PaymentRecordsTable;
use App\Models\PaymentRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;


class PaymentRecordResource extends Resource
{
    protected static ?string $model = PaymentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    
    public static function form(Schema $schema): Schema
    {
        return PaymentRecordForm::configure($schema);
    }
    
    public static function table(Table $table): Table
    {
        return PaymentRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentRecords::route('/'),
            'create' => CreatePaymentRecord::route('/create'),
            'edit' => EditPaymentRecord::route('/{record}/edit'),
        ];
    }
}
