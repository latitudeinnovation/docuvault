<?php

namespace App\Models;

use App\Enums\DocumentType as DocumentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Admin-managed list of document types. The built-in cases also live in
 * App\Enums\DocumentType (used for type-safe references in code); these rows are
 * the source of truth for what is shown in the company tabs and the upload form.
 *
 * @property string $key
 * @property string $label
 * @property string $color
 * @property string $icon
 * @property bool $is_system
 * @property int $sort_order
 */
class DocumentType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'color',
        'icon',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Built-in types back the in-code enum cases; they may be relabelled but
        // never deleted (also guards bulk deletes).
        static::deleting(function (self $type): bool {
            return ! $type->is_system;
        });
    }

    /**
     * Resolve a stored document_type value to its presentation row. Falls back to
     * a transient row built from the built-in enum so unknown/legacy keys still
     * render a label, icon and colour.
     */
    public static function resolve(?string $key): self
    {
        $key = (string) $key;

        $row = static::query()->where('key', $key)->first();

        if ($row instanceof self) {
            return $row;
        }

        $enum = DocumentTypeEnum::fromValue($key);

        return new self([
            'key' => $enum->value,
            'label' => (string) $enum->getLabel(),
            'color' => (string) $enum->getColor(),
            'icon' => 'heroicon-o-'.$enum->getIcon()->value,
        ]);
    }

    /**
     * All types in display order, keyed by their stored value.
     *
     * @return Collection<string, self>
     */
    public static function ordered(): Collection
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->keyBy('key');
    }

    /**
     * Pick a sensible Heroicon for a type based on words in its label, so the
     * admin never has to choose one. Falls back to a generic document icon.
     */
    public static function iconForLabel(string $label): string
    {
        $label = Str::lower($label);

        $map = [
            'heroicon-o-banknotes' => ['bank', 'account', 'statement', 'finance', 'cash', 'deposit'],
            'heroicon-o-building-office-2' => ['ssm', 'company', 'corporate', 'business', 'registration', 'incorporat'],
            'heroicon-o-document-currency-dollar' => ['invoice', 'receipt', 'bill', 'payment', 'tax', 'gst', 'salary', 'payroll', 'quotation'],
            'heroicon-o-users' => ['director', 'officer', 'member', 'shareholder', 'staff', 'employee', 'people'],
            'heroicon-o-identification' => ['identity', 'passport', 'licence', 'license', 'nric'],
            'heroicon-o-scale' => ['contract', 'agreement', 'legal', 'law', 'court'],
            'heroicon-o-envelope' => ['letter', 'mail', 'correspondence', 'notice'],
            'heroicon-o-home' => ['property', 'land', 'house', 'asset', 'deed', 'tenancy'],
        ];

        foreach ($map as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($label, $keyword)) {
                    return $icon;
                }
            }
        }

        return 'heroicon-o-document-text';
    }

    /**
     * Options for a document-type Select, in display order.
     *
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'key')
            ->all();
    }
}
