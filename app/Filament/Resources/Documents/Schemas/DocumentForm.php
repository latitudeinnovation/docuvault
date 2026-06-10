<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('user_id')
                                ->label('Owner')
                                ->relationship('owner', 'name')
                                ->searchable()
                                ->preload()
                                ->default(fn (): ?int => auth()->id())
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('document_type')
                                ->default(config('docuvault.documents.default_type'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            Select::make('status')
                                ->options(DocumentStatus::class)
                                ->default(DocumentStatus::Uploaded)
                                ->required()
                                ->columnSpan(1),
                        ]),

                        FileUpload::make('file_path')
                            ->label('Document file')
                            ->disk(config('docuvault.documents.disk'))
                            ->directory(config('docuvault.documents.directory'))
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/tiff',
                            ])
                            ->storeFileNamesIn('original_file_name')
                            ->preventFilePathTampering()
                            ->maxSize(config('docuvault.documents.max_upload_size'))
                            ->required()
                            ->columnSpanFull(),

                        Hidden::make('file_disk')
                            ->default(config('docuvault.documents.disk'))
                            ->required(),

                        Hidden::make('file_type')
                            ->default('application/octet-stream')
                            ->required(),
                    ]),
            ]);
    }
}
