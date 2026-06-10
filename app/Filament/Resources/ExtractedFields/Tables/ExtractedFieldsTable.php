<?php

namespace App\Filament\Resources\ExtractedFields\Tables;

use App\Enums\ExtractedFieldStatus;
use App\Models\ExtractedField;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExtractedFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document.title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('field_key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('field_label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('corrected_value')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('confidence')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('document')
                    ->relationship('document', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(ExtractedFieldStatus::class),
                Filter::make('low_confidence')
                    ->query(fn (Builder $query): Builder => $query->where('confidence', '<', 0.8)),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->action(fn (ExtractedField $record) => $record->update(['status' => ExtractedFieldStatus::Approved])),
                Action::make('reject')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ExtractedField $record) => $record->update(['status' => ExtractedFieldStatus::Rejected])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
