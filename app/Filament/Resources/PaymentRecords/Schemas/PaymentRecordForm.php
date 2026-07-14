<?php

namespace App\Filament\Resources\PaymentRecords\Schemas;

use App\Models\BankAccount;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->label('Main Account')
                    ->relationship(
                        name: 'company',
                        titleAttribute: 'name',
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

                Select::make('bank_account')
                    ->label('Bank Account')
                    ->options(function (Get $get) {
                        $companyId = $get('company_id');

                        if (! $companyId) {
                            return [];
                        }

                        return BankAccount::query()
                            ->where('company_id', $companyId)
                            ->get()
                            ->mapWithKeys(fn (BankAccount $account) => [
                                $account->account_no => "{$account->bank_name} - {$account->account_no}",
                            ]);
                    })
                    ->searchable()
                    ->disabled(fn (Get $get) => ! $get('company_id'))
                    ->required(),

                TextInput::make('category')
                    ->label('Category')
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