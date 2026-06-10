<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\ExtractedFields\Tables\ExtractedFieldsTable;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ExtractedFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'extractedFields';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('field_key')
                    ->required()
                    ->maxLength(255),
                TextInput::make('field_label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('confidence')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                Select::make('status')
                    ->options(ExtractedFieldStatus::class)
                    ->default(ExtractedFieldStatus::Pending)
                    ->required(),
                Textarea::make('value')
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('corrected_value')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return ExtractedFieldsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['status'] ??= ExtractedFieldStatus::Pending;

                        return $data;
                    }),
            ]);
    }
}
