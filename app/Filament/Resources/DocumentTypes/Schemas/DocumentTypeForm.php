<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document type')
                    ->schema([
                        // The stored `key` and `icon` are derived from the label
                        // (see CreateDocumentType / EditDocumentType) so the admin
                        // only ever provides a label, colour and order.
                        Grid::make(2)->schema([
                            TextInput::make('label')
                                ->required()
                                ->maxLength(255),

                            Select::make('color')
                                ->options([
                                    'gray' => 'Gray',
                                    'primary' => 'Primary',
                                    'info' => 'Info',
                                    'success' => 'Success',
                                    'warning' => 'Warning',
                                    'danger' => 'Danger',
                                ])
                                ->default('gray')
                                ->required(),

                            TextInput::make('sort_order')
                                ->label('Order')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->helperText('Lower numbers appear first.'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
