<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\DocumentType;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount([
                    'documents',
                    'documents as ssm_count' => fn (Builder $q) => $q->where('document_type', DocumentType::Ssm->value),
                    'documents as bank_account_count' => fn (Builder $q) => $q->where('document_type', DocumentType::BankAccount->value),
                ]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->badge()
                    ->sortable(),
                TextColumn::make('ssm_count')
                    ->label('SSM')
                    ->badge()
                    ->color(DocumentType::Ssm->getColor()),
                TextColumn::make('bank_account_count')
                    ->label('Bank Account')
                    ->badge()
                    ->color(DocumentType::BankAccount->getColor()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordUrl(fn ($record): string => CompanyResource::getUrl('view', ['record' => $record]));
    }
}
