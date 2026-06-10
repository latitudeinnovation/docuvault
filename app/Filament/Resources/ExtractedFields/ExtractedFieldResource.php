<?php

namespace App\Filament\Resources\ExtractedFields;

use App\Filament\Resources\ExtractedFields\Pages\CreateExtractedField;
use App\Filament\Resources\ExtractedFields\Pages\EditExtractedField;
use App\Filament\Resources\ExtractedFields\Pages\ListExtractedFields;
use App\Filament\Resources\ExtractedFields\Pages\ViewExtractedField;
use App\Filament\Resources\ExtractedFields\Schemas\ExtractedFieldForm;
use App\Filament\Resources\ExtractedFields\Schemas\ExtractedFieldInfolist;
use App\Filament\Resources\ExtractedFields\Tables\ExtractedFieldsTable;
use App\Models\ExtractedField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExtractedFieldResource extends Resource
{
    protected static ?string $model = ExtractedField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'field_label';

    public static function form(Schema $schema): Schema
    {
        return ExtractedFieldForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtractedFieldInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtractedFieldsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExtractedFields::route('/'),
            'create' => CreateExtractedField::route('/create'),
            'view' => ViewExtractedField::route('/{record}'),
            'edit' => EditExtractedField::route('/{record}/edit'),
        ];
    }
}
