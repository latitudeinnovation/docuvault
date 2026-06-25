<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\DirectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'name',
    'ic_passport',
    'slug',
    'address',
])]
class Director extends Model
{
    /** @use HasFactory<DirectorFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['designation', 'appointed_at', 'source_document_id'])
            ->withTimestamps();
    }

    /**
     * Distinct designations this person holds across companies (e.g. Director, Secretary).
     *
     * @return Collection<int, string>
     */
    public function roles(): Collection
    {
        return $this->companies
            ->map(fn (Company $company): ?string => $company->pivot->designation)
            ->filter()
            ->map(fn (string $role): string => Str::title(mb_strtolower($role)))
            ->unique()
            ->values();
    }

    /**
     * Bank statements held under this person's name — bank-account documents
     * whose extracted account-holder field matches the director's name. Driven
     * by the AI-extracted account holder, independent of company linkage.
     *
     * @return Collection<int, Document>
     */
    public function bankStatements(): Collection
    {
        $nameSlug = Str::slug($this->name);

        if ($nameSlug === '') {
            return collect();
        }

        return Document::query()
            ->where('user_id', $this->user_id)
            ->where('document_type', DocumentType::BankAccount->value)
            ->with('extractedFields')
            ->get()
            ->filter(fn (Document $document): bool => $this->accountHolderSlug($document) === $nameSlug)
            ->values();
    }

    /**
     * Slug of a bank document's extracted account-holder name, or null.
     */
    private function accountHolderSlug(Document $document): ?string
    {
        foreach ($document->extractedFields as $field) {
            $key = strtolower((string) $field->field_key);

            if (str_contains($key, 'account_holder') || str_contains($key, 'account_name') || $key === 'name') {
                $value = (string) ($field->corrected_value ?? $field->value ?? '');

                if (trim($value) !== '') {
                    return Str::slug($value);
                }
            }
        }

        return null;
    }

    /**
     * Identity slug: the IC/passport digits when present (most reliable), else a
     * slug of the name. Lets the same person dedupe across SSM documents.
     */
    public static function slugFor(?string $ic, string $name): string
    {
        $digits = preg_replace('/\D+/', '', (string) $ic);

        if (is_string($digits) && $digits !== '') {
            return 'ic-'.$digits;
        }

        return Str::slug($name) ?: md5($name);
    }
}
