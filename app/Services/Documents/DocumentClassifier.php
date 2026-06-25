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
     * Find (or create) the company a document belongs to, based on its already
     * persisted extracted fields. Returns null when no company name is present.
     */
    public function resolveCompany(Document $document): ?Company
    {
        $name = $this->companyNameFromFields($document);

        if ($name === null) {
            return null;
        }

        $slug = Company::slugFor($name);

        return Company::firstOrCreate(
            ['user_id' => $document->user_id, 'slug' => $slug],
            ['name' => $name],
        );
    }

    /**
     * Pick the best company-name value from the document's extracted fields,
     * honouring the configured key priority.
     */
    private function companyNameFromFields(Document $document): ?string
    {
        /** @var array<int, string> $nameKeys */
        $nameKeys = config('docuvault.company.name_keys', []);

        $fields = $document->extractedFields()
            ->get(['field_key', 'value', 'corrected_value'])
            ->mapWithKeys(fn ($field): array => [
                $this->normalizeKey((string) $field->field_key) => $this->normalizeValue(
                    $field->corrected_value ?? $field->value,
                ),
            ])
            ->filter()
            ->all();

        foreach ($nameKeys as $key) {
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
     * Clean a raw value into a usable company name, or null when unusable
     * (empty, or a pure number/ID/date that can't be a company name).
     */
    private function normalizeValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        // Take the first line and collapse internal whitespace.
        $value = trim((string) preg_replace('/\s+/', ' ', strtok($value, "\n")));

        if ($value === '' || preg_match('/^[\d\-\/.\s]+$/', $value)) {
            return null;
        }

        return Str::limit($value, 255, '');
    }
}
