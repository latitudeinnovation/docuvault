# Manual "Link & Sync" Action — Design

## Problem

Documents auto-link to a company and sync entity records (BankAccount, Director,
Shareholder) during `ProcessDocumentWithRaraxuan`. This link resolution can fail
silently — e.g. a bank statement's account number has no matching `BankAccount`
row yet, so `DocumentClassifier::resolveCompany(create: false)` returns null and
the document ends up unlinked with no entity records created. There's also no
way to re-run the sync after correcting a field's value, or to move a document
to a different company after the fact.

## Solution

Add a manual "Link & Sync" header action on the document view page
(`app/Filament/Resources/Documents/Pages/ViewDocument.php`) that lets a user
pick (or create) a company and re-run the appropriate entity extractor.

### Visibility

Same gate as the existing `approve` action: visible only when the document has
extracted fields and none are `ExtractedFieldStatus::Pending`. Unlike
`approve`, it stays visible after approval too (a doc can be re-linked/re-synced
any time post-approval).

### Form

A modal with a single Filament `Select` field:

- **Options**: companies owned by `document.user_id`, searchable.
- **Default value**: `$record->company_id` if already set, so re-clicking to
  re-sync (after correcting field values) requires no re-pick.
- **Create option** (`createOptionForm`): Name + Registration No. fields,
  prefilled from the document's own extracted fields via two new public
  methods on `DocumentClassifier`:
  - `extractedCompanyName(Document $document): ?string`
  - `extractedRegistrationNo(Document $document): ?string`

  Both delegate to the existing private `firstFieldValue()` (already used
  internally by `resolveCompany()`), just exposed for reuse. No duplicate
  extraction logic.

### Submit behavior

1. `$document->update(['company_id' => $selectedCompanyId])`.
2. Dispatch the sync appropriate to `document.document_type`:
   - `ssm` → `DirectorExtractor::syncFromDocument($document, $company)` +
     `ShareholderExtractor::syncFromDocument($document, $company)`
   - `bank_account` → `BankAccountExtractor::syncFromDocument($document, $company)`
   - `general` → link only, no extractor call (nothing to sync)
3. Success notification: "Linked to {company name} and synced."

### Idempotency / no-cleanup

Extractors are already idempotent (`firstOrCreate` keyed by
`company_id + slug`), so re-running against the *same* company only backfills
empty fields — no duplicate rows.

If the user picks a **different** company than the one currently linked,
entity rows created under the old company are left as-is — **no automatic
cleanup/detach**. This is a deliberate choice to avoid accidental data loss;
stale rows can be manually deleted if needed.

## Non-goals

- No change to the automatic processing pipeline's company-resolution logic.
- No bulk/batch link-sync action — one document at a time via this button.
- No UI to browse/clean up orphaned entity rows left behind by a company
  switch — out of scope for this change.

## Files touched

- `app/Filament/Resources/Documents/Pages/ViewDocument.php` — new action.
- `app/Services/Documents/DocumentClassifier.php` — two new public wrapper
  methods for form field prefill.
