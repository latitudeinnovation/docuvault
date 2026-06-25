<?php

namespace App\Filament\Resources\Shareholders\Pages;

use App\Filament\Resources\Shareholders\ShareholderResource;
use Filament\Resources\Pages\ListRecords;

class ListShareholders extends ListRecords
{
    protected static string $resource = ShareholderResource::class;
}
