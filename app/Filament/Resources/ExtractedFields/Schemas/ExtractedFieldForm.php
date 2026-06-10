<?php

namespace App\Filament\Resources\ExtractedFields\Schemas;

use App\Enums\ExtractedFieldStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExtractedFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Extracted field')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('document_id')
                                ->relationship('document', 'title')
                                ->searchable()
                                ->preload()
                                ->required(),
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
                        ]),
                        Textarea::make('value')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('corrected_value')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
