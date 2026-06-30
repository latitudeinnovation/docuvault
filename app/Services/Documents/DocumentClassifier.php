<?php

namespace App\Services\Documents;

use App\Enums\DocumentType;
use App\Models\Company;
use App\Models\Document;
use Illuminate\Support\Str;

class DocumentClassifier
{
    /**
     * Map the AI-returned document_type onto a canonical DocumentType value.
     *
     * @param  array<string, mixed>  $normalized  The normalized extraction result.
     */
    public function resolveType(array $normalized): string
    {
        $raw = Str::lower(trim((string) data_get($normalized, 'document_type')));

        if ($raw === '') {
            return DocumentType::General->value;
        }

        /** @var array<string, array<int, string>> $map */
        $map = config('docuvault.documents.type_map', []);

        foreach ($map as $canonical => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($raw, Str::lower($keyword))) {
                    return $canonical;
                }
            }
        }

        return DocumentType::General->value;
    }

    /**
     * Resolve the company a document belongs to, based on its already persisted
     * extracted fields. Returns null when no company name is present.
     *
     * When $create is true (SSM documents) a company is created if none exists
     * and its registration number is backfilled. When false (other types) the
     * document only links to an already-existing company by name, never creating
     * one nor mutating its details.
     */
    public function resolveCompany(Document $document, bool $create = true): ?Company
    {
        $name = $this->firstFieldValue($document, config('docuvault.company.name_keys', []), rejectNumeric: true);

        if ($name === null) {
            return null;
        }

        $slug = Company::slugFor($name);

        if (! $create) {
            return Company::query()
                ->where('user_id', $document->user_id)
                ->where('slug', $slug)
                ->first();
        }

        $company = Company::firstOrCreate(
            ['user_id' => $document->user_id, 'slug' => $slug],
            ['name' => $name],
        );

        // Backfill the registration number from this document (e.g. an SSM
        // profile) when the company does not have one yet. Registration numbers
        // are IDs, so they must not be rejected as "numeric" like names are.
        if (blank($company->registration_no)) {
            $registration = $this->firstFieldValue($document, config('docuvault.company.registration_keys', []), rejectNumeric: false);

            if ($registration !== null) {
                $company->forceFill(['registration_no' => $registration])->save();
            }
        }

        return $company;
    }

    /**
     * Return the first extracted-field value whose key matches one of the given
     * keys (in priority order), normalized for display. When $rejectNumeric is
     * true, pure number/date/ID values are skipped (used for names).
     *
     * @param  array<int, string>  $keys
     */
    private function firstFieldValue(Document $document, array $keys, bool $rejectNumeric): ?string
    {
        $fields = $document->extractedFields()
            ->get(['field_key', 'value', 'corrected_value'])
            ->mapWithKeys(fn ($field): array => [
                $this->normalizeKey((string) $field->field_key) => $this->normalizeValue(
                    $field->corrected_value ?? $field->value,
                    $rejectNumeric,
                ),
            ])
            ->filter()
            ->all();

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);

            if (isset($fields[$normalizedKey])) {
                return $fields[$normalizedKey];
            }
        }

        return null;
    }

    /**
     * Normalize a field key for matching: last dotted segment, lowercased,
     * non-alphanumerics collapsed to underscores (e.g. "data.Company Name" → "company_name").
     */
    private function normalizeKey(string $key): string
    {
        $segment = Str::afterLast($key, '.');

        return trim(preg_replace('/[^a-z0-9]+/', '_', Str::lower($segment)) ?? '', '_');
    }

    /**
     * Clean a raw value into a usable string, or null when unusable. When
     * $rejectNumeric is true, pure number/ID/date values are rejected (so a
     * company name never resolves to e.g. an IC number); registration numbers
     * pass $rejectNumeric = false since they are legitimately numeric.
     */
    private function normalizeValue(mixed $value, bool $rejectNumeric = true): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        // Take the first line and collapse internal whitespace.
        $value = trim((string) preg_replace('/\s+/', ' ', strtok($value, "\n")));

        if ($value === '') {
            return null;
        }

        if ($rejectNumeric && preg_match('/^[\d\-\/.\s]+$/', $value)) {
            return null;
        }

        return Str::limit($value, 255, '');
    }
}
