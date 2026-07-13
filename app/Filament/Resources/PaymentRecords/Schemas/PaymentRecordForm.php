<?php

namespace App\Filament\Resources\PaymentRecords\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([  // <-- FIX 1: Changed "components" to "schema"
                Select::make('transaction_type')
                    ->label('Type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
                    ->required(),

                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    // FIX 2: Removed the "Set" type hint to clear the editor error
                    ->afterStateUpdated(fn ($set) => $set('bank_account', null)),

                TextInput::make('bank_account')
                    ->label('Bank Account')
                    ->required(),

                TextInput::make('category')
                    ->label('Incoming Category')
                    ->required(),

                TextInput::make('main_account')
                    ->label('Main Account')
                    ->required(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->prefix('RM')
                    ->required(),

                Textarea::make('remarks')
                    ->label('Remarks')
                    ->columnSpanFull(),
            ]);
    }
}