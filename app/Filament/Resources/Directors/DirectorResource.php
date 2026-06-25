<?php

namespace App\Filament\Resources\Directors;

use App\Filament\Resources\Directors\Pages\ListDirectors;
use App\Filament\Resources\Directors\Pages\ViewDirector;
use App\Filament\Resources\Directors\Tables\DirectorsTable;
use App\Models\Director;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DirectorResource extends Resource
{
    protected static ?string $model = Director::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \UnitEnum|string|null $navigationGroup = 'Entities';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Directors';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Director')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('ic_passport')
                        ->label('IC / Passport')
                        ->placeholder('—'),
                    TextEntry::make('address')
                        ->placeholder('—'),
                ]),

            Section::make('Companies & bank statements')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('holdings')
                        ->hiddenLabel()
                        ->view('filament.directors.holdings'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return DirectorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectors::route('/'),
            'view' => ViewDirector::route('/{record}'),
        ];
    }
}
