<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
use App\Models\ExtractedField;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', Document::count())
                ->description('All uploaded documents')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Pending Review', Document::where('status', DocumentStatus::NeedsReview)->count())
                ->description('Awaiting human review')
                ->descriptionIcon('heroicon-o-document-magnifying-glass')
                ->color('warning'),

            Stat::make('Approved', Document::where('status', DocumentStatus::Approved)->count())
                ->description('Fully approved documents')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Processing', Document::where('status', DocumentStatus::Processing)->count())
                ->description('Currently being processed')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info'),

            Stat::make('Failed', Document::where('status', DocumentStatus::Failed)->count())
                ->description('Processing failed')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Fields Pending Approval', ExtractedField::where('status', ExtractedFieldStatus::Pending)->count())
                ->description('Extracted fields to review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
