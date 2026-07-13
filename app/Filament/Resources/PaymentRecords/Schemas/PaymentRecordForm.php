<?php

namespace App\Filament\Resources\PaymentRecords\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth; // Import the Auth facade

class PaymentRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('transaction_type')
                    ->label('Type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
                    ->required(),

                Select::make('company_id')
                    ->label('Company')
                    ->relationship(
                        name: 'company', 
                        titleAttribute: 'name',
                        // Replaced auth()->id() with Auth::id() to clear the IDE warning
                        modifyQueryUsing: fn ($query) => $query->where('user_id', Auth::id())
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('bank_account', null))
                    ->loadingMessage('Loading corporate profiles...')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data) {
                        // Replaced auth()->id() with Auth::id() here as well
                        $company = Company::create([
                            'user_id' => Auth::id(),
                            'name' => $data['name'],
                            'slug' => Company::slugFor($data['name']),
                        ]);
                        
                        return $company->getKey();
                    }),

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