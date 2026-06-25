<?php

namespace App\Filament\Resources\Shareholders;

use App\Filament\Resources\Shareholders\Pages\ListShareholders;
use App\Filament\Resources\Shareholders\Pages\ViewShareholder;
use App\Filament\Resources\Shareholders\Tables\ShareholdersTable;
use App\Models\Shareholder;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShareholderResource extends Resource
{
    protected static ?string $model = Shareholder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \UnitEnum|string|null $navigationGroup = 'Entities';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Shareholders';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shareholder')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('ic_passport')
                        ->label('IC / Passport')
                        ->placeholder('—'),
                ]),

            Section::make('Companies & shareholdings')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('holdings')
                        ->hiddenLabel()
                        ->view('filament.shareholders.holdings'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ShareholdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShareholders::route('/'),
            'view' => ViewShareholder::route('/{record}'),
        ];
    }
}
