<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function render(): View
    {
        return view('filament.companies.documents-relation-manager', [
            'company' => $this->getOwnerRecord(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
