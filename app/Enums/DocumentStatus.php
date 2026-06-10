<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DocumentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Failed = 'failed';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Uploaded => 'Uploaded',
            self::Processing => 'Processing',
            self::NeedsReview => 'Needs review',
            self::Approved => 'Approved',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Uploaded => 'gray',
            self::Processing => 'info',
            self::NeedsReview => 'warning',
            self::Approved => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Uploaded => Heroicon::DocumentArrowUp,
            self::Processing => Heroicon::ArrowPath,
            self::NeedsReview => Heroicon::DocumentMagnifyingGlass,
            self::Approved => Heroicon::CheckCircle,
            self::Failed => Heroicon::ExclamationTriangle,
        };
    }
}
