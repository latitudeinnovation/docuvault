<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 0;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('registration_no')
                        ->label('Registration no.')
                        ->placeholder('—'),
                    TextEntry::make('documents_count')
                        ->label('Documents')
                        ->badge()
                        ->state(fn (Company $record): int => $record->documents()->count()),
                ]),

            Section::make('Documents')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('documents_tabs')
                        ->hiddenLabel()
                        ->view('filament.companies.documents-tabs'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }
}
