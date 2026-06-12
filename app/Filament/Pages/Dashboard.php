<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentDocumentsWidget;
use App\Filament\Widgets\RecentExtractedFieldsWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Home;
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RecentDocumentsWidget::class,
            RecentExtractedFieldsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
