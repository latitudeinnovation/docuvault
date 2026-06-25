<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DocumentType: string implements HasColor, HasIcon, HasLabel
{
    case Ssm = 'ssm';
    case BankAccount = 'bank_account';
    case General = 'general';

    /**
     * Resolve a raw document_type string to a known case, falling back to General.
     */
    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::General;
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Ssm => 'SSM',
            self::BankAccount => 'Bank Account',
            self::General => 'General',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Ssm => 'info',
            self::BankAccount => 'success',
            self::General => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Ssm => Heroicon::BuildingOffice2,
            self::BankAccount => Heroicon::Banknotes,
            self::General => Heroicon::DocumentText,
        };
    }
}
