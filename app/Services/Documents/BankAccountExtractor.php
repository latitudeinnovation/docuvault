<?php

namespace App\Services\Documents;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Document;
use Illuminate\Support\Str;

class BankAccountExtractor
{
    /**
     * Resolve the bank account referenced by a document's already-persisted
     * extracted fields and attach it to the given company (deduping by a
     * digits-only account-number slug). No-op when no account number is
     * present.
     */
    public function syncFromDocument(Document $document, Company $company): void
    {
        $fields = $this->normalizedFields($document);

        $accountNo = $this->firstValue($fields, config('docuvault.bank_accounts.account_no_keys', []));

        if ($accountNo === null) {
            return;
        }

        $accountHolderName = $this->firstValue($fields, config('docuvault.bank_accounts.account_holder_keys', []));
        $bankName = $this->firstValue($fields, config('docuvault.bank_accounts.bank_name_keys', []));
        $accountType = $this->firstValue($fields, config('docuvault.bank_accounts.account_type_keys', []));

        $bankAccount = BankAccount::firstOrCreate(
            ['company_id' => $company->getKey(), 'slug' => BankAccount::slugFor($accountNo)],
            [
                'user_id' => $document->user_id,
                'account_no' => $accountNo,
                'account_holder_name' => $accountHolderName,
                'bank_name' => $bankName,
                'account_type' => $accountType,
            ],
        );

        // Backfill nullable fields from a later document when initially missing;
        // always point at the most recently processed source document.
        $bankAccount->forceFill([
            'account_holder_name' => $bankAccount->account_holder_name ?: $accountHolderName,
            'bank_name' => $bankAccount->bank_name ?: $bankName,
            'account_type' => $bankAccount->account_type ?: $accountType,
            'source_document_id' => $document->getKey(),
        ])->save();
    }

    /**
     * Normalized field_key => trimmed value, for every extracted field with a
     * non-empty value.
     *
     * @return array<string, string>
     */
    private function normalizedFields(Document $document): array
    {
        return $document->extractedFields()
            ->get(['field_key', 'value', 'corrected_value'])
            ->mapWithKeys(fn ($field): array => [
                $this->normalizeKey((string) $field->field_key) => trim((string) ($field->corrected_value ?? $field->value ?? '')),
            ])
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }

    /**
     * @param  array<string, string>  $fields
     * @param  array<int, string>  $keys
     */
    private function firstValue(array $fields, array $keys): ?string
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeKey($key);

            if (isset($fields[$normalized])) {
                return $fields[$normalized];
            }
        }

        return null;
    }

    /**
     * Normalize a field key for matching: last dotted segment, lowercased,
     * non-alphanumerics collapsed to underscores.
     */
    private function normalizeKey(string $key): string
    {
        $segment = Str::afterLast($key, '.');

        return trim(preg_replace('/[^a-z0-9]+/', '_', Str::lower($segment)) ?? '', '_');
    }
}
