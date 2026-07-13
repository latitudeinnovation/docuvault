<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\ExtractedField;

class ExtractedFieldObserver
{
    /**
     * Handle the ExtractedField "saved" event.
     */
    public function saved(ExtractedField $extractedField): void
    {
        // 1. Only run if this specific field represents the Company Name
        if ($extractedField->field_key !== 'Company_Name') {
            return;
        }

        // 2. Prioritize human-corrected values over raw AI data
        $companyName = trim($extractedField->corrected_value ?? $extractedField->value ?? '');

        if ($companyName === '') {
            return;
        }

        // 3. Retrieve the user ID from the parent document
        // We use the relationship defined in ExtractedField
        $userId = $extractedField->document?->user_id;

        if (! $userId) {
            return;
        }

        // 4. Generate a stable slug using the Company model's built-in helper method
        $slug = Company::slugFor($companyName);

        // 5. Automatically create the company if it doesn't already exist for this user.
        $company = Company::firstOrCreate(
            [
                'user_id' => $userId,
                'slug' => $slug,
            ],
            [
                'name' => $companyName,
            ]
        );

        // 6. Automatically link the parent document to this newly verified company
        $document = $extractedField->document;
        if ($document && $document->company_id !== $company->id) {
            $document->updateQuietly(['company_id' => $company->id]);
        }
    }
}