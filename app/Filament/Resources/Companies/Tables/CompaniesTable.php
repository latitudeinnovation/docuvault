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
                    'documents as bank_doc_count' => fn (Builder $q) => $q->where('document_type', DocumentType::BankAccount->value),
                    'documents as general_doc_count' => fn (Builder $q) => $q->where('document_type', DocumentType::General->value),
                    'directors',
                    'shareholders',
                    'bankAccounts',
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
                    ->color(DocumentType::Ssm->getColor())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_doc_count')
                    ->label('Bank Docs')
                    ->badge()
                    ->color(DocumentType::BankAccount->getColor())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('general_doc_count')
                    ->label('General')
                    ->badge()
                    ->color(DocumentType::General->getColor())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('directors_count')
                    ->label('Directors')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('shareholders_count')
                    ->label('Shareholders')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('bank_accounts_count')
                    ->label('Bank Accounts')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordUrl(fn ($record): string => CompanyResource::getUrl('view', ['record' => $record]));
    }
}
