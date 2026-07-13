# Bank Account Model Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give bank accounts a real, deduped Eloquent model (`BankAccount`) — like `Company`/`Director`/`Shareholder` — so other Filament resources (e.g. a future Payment In/Out resource) can bind a real FK dropdown to it instead of reading loose `ExtractedField` rows.

**Architecture:** Mirror the existing `Director`/`Shareholder` extraction pattern. `ProcessDocumentWithRaraxuan` already resolves a `Company` per document and, for SSM documents, calls `DirectorExtractor`/`ShareholderExtractor` to sync structured child records from the extracted data. This plan adds a `BankAccount` model (`belongsTo Company`, `belongsTo User`, `belongsTo Document` as `sourceDocument`) and a `BankAccountExtractor` service that reads the document's already-persisted `ExtractedField` rows (bank fields are flat key/value, not a table like officers/shareholders), resolves `account_no`/`account_holder_name`/`bank_name`/`account_type` via configurable key matching (same technique `DocumentClassifier::firstFieldValue` already uses), and upserts one `BankAccount` per `(company_id, slug)` where `slug` is the account number normalized to digits-only. This directly fixes the duplicate-row problem visible today (uploading two statements for the same account currently produces two disconnected `ExtractedField` sets with no shared identity).

**Tech Stack:** Laravel 13, PHP 8.4, Filament 5, MariaDB, PHPUnit (Feature tests use `RefreshDatabase` + `Livewire::test`).

## Global Constraints

- Follow the existing `Director`/`Shareholder` code shape exactly: `#[Fillable([...])]` attribute on the model, `HasFactory`, `belongsTo`/`hasMany` relation methods, a dedicated `*Extractor` service class with a `syncFromDocument(Document $document, Company $company): void` method, config-driven key matching in `config/docuvault.php`.
- `BankAccount.company_id` is **NOT NULL** — a `BankAccount` is only ever synced when a `Company` has already been resolved for the document (same gating style as the existing `$company !== null && $isSsm` check in `ProcessDocumentWithRaraxuan`).
- Dedupe key: `BankAccount::slugFor($accountNo)` strips everything but digits (e.g. `"2622-0500-0947"` and `"262205000947"` must resolve to the same record). Uniqueness is scoped per `(company_id, slug)`.
- Out of scope for this plan (do not build): a standalone `BankAccountResource` with list/view Filament pages and navigation entry, and any Payment In/Out resource — neither exists in this repo yet. Only a read-only "Bank Accounts" tab on the existing `CompanyResource` view page is in scope, mirroring the existing Directors/Shareholders tabs.
- Test style: PHPUnit Feature tests using `RefreshDatabase`, following the structure of `tests/Feature/DirectorExtractorTest.php` and `tests/Feature/ProcessDocumentWithRaraxuanTest.php`. Do not use PHPUnit's `MockObject` — this codebase constructs real models via factories.

---

### Task 1: `BankAccount` model, migration, and `Company::bankAccounts()` relation

**Files:**
- Create: `database/migrations/2026_07_13_000000_create_bank_accounts_table.php`
- Create: `app/Models/BankAccount.php`
- Create: `database/factories/BankAccountFactory.php`
- Modify: `app/Models/Company.php`
- Test: `tests/Feature/BankAccountModelTest.php`

**Interfaces:**
- Produces: `App\Models\BankAccount` with fillable `user_id, company_id, account_no, slug, account_holder_name, bank_name, account_type, currency, source_document_id`; relations `owner(): BelongsTo`, `company(): BelongsTo`, `sourceDocument(): BelongsTo`; static `BankAccount::slugFor(string $accountNo): string`.
- Produces: `Company::bankAccounts(): HasMany` (returns `BankAccount` records for that company).
- Produces: `BankAccount::factory()` via `BankAccountFactory`.

- [ ] **Step 1: Write the migration**

Create `database/migrations/2026_07_13_000000_create_bank_accounts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('account_no');
            $table->string('slug');
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
```

- [ ] **Step 2: Write the `BankAccount` model**

Create `app/Models/BankAccount.php`:

```php
<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'company_id',
    'account_no',
    'slug',
    'account_holder_name',
    'bank_name',
    'account_type',
    'currency',
    'source_document_id',
])]
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    /**
     * Dedupe key for an account number: digits only when present (stable
     * across formatting variance like dashes/spaces), else a slug of the
     * raw string.
     */
    public static function slugFor(string $accountNo): string
    {
        $digits = preg_replace('/\D+/', '', $accountNo);

        if (is_string($digits) && $digits !== '') {
            return $digits;
        }

        return Str::slug($accountNo) ?: md5($accountNo);
    }
}
```

- [ ] **Step 3: Write the factory**

Create `database/factories/BankAccountFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountNo = fake()->numerify('##########');

        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'account_no' => $accountNo,
            'slug' => BankAccount::slugFor($accountNo),
            'account_holder_name' => fake()->company(),
            'bank_name' => fake()->randomElement(['Maybank', 'RHB', 'HLB PrimeBiz', 'CIMB']),
            'account_type' => fake()->randomElement(['Current Account', 'Savings Account']),
            'currency' => 'MYR',
        ];
    }
}
```

- [ ] **Step 4: Add the `bankAccounts()` relation to `Company`**

In `app/Models/Company.php`, add the import and method alongside the existing `directors()`/`shareholders()` relations:

```php
    public function shareholders(): BelongsToMany
    {
        return $this->belongsToMany(Shareholder::class)
            ->withPivot(['shares', 'source_document_id'])
            ->withTimestamps();
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
```

(`HasMany` is already imported in this file for the existing `documents()` relation — no new import needed.)

- [ ] **Step 5: Write the model test**

Create `tests/Feature/BankAccountModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_account_belongs_to_company_and_is_listed_on_it(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '262205000947',
        ]);

        $this->assertTrue($company->bankAccounts->contains($bankAccount));
        $this->assertSame($company->id, $bankAccount->company->id);
    }

    public function test_slug_for_normalizes_to_digits_only(): void
    {
        $this->assertSame('262205000947', BankAccount::slugFor('262205000947'));
        $this->assertSame('262205000947', BankAccount::slugFor('2622-0500-0947'));
    }

    public function test_company_and_slug_are_unique_together(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '111',
            'slug' => BankAccount::slugFor('111'),
        ]);

        $this->expectException(QueryException::class);

        BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '111',
            'slug' => BankAccount::slugFor('111'),
        ]);
    }
}
```

- [ ] **Step 6: Run migrations and the test**

Run: `php artisan migrate --force && php artisan test tests/Feature/BankAccountModelTest.php`
Expected: migration runs cleanly, all 3 tests PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_13_000000_create_bank_accounts_table.php app/Models/BankAccount.php database/factories/BankAccountFactory.php app/Models/Company.php tests/Feature/BankAccountModelTest.php
git commit -m "feat: add BankAccount model with Company relation"
```

---

### Task 2: `BankAccountExtractor` service + config key mapping

**Files:**
- Modify: `config/docuvault.php`
- Create: `app/Services/Documents/BankAccountExtractor.php`
- Test: `tests/Feature/BankAccountExtractorTest.php`

**Interfaces:**
- Consumes: `App\Models\BankAccount::slugFor(string $accountNo): string` (Task 1), `Document::extractedFields(): HasMany` (existing), `App\Models\ExtractedField` (existing, has `field_key`, `value`, `corrected_value`).
- Produces: `App\Services\Documents\BankAccountExtractor::syncFromDocument(Document $document, Company $company): void` — no-op when no account number field is found; otherwise creates or updates exactly one `BankAccount` per `(company_id, slug)`.

- [ ] **Step 1: Add the `bank_accounts` config block**

In `config/docuvault.php`, the file currently ends with this (lines 91-104):

```php
    'shareholders' => [
        'table_keys' => ['shareholder', 'member'],

        'columns' => [
            'name' => ['name', 'company'],
            'ic' => ['ic', 'passport', 'registration', 'nric'],
            'shares' => ['share', 'shares', 'total'],
        ],
    ],

    'raraxuan' => [
        'document_agent' => env('RARAXUAN_DOCUMENT_AGENT', 'doc-universal-extractor'),
    ],
];
```

Replace it with (inserting the new `bank_accounts` block between `shareholders` and `raraxuan`, keeping everything else unchanged):

```php
    'shareholders' => [
        'table_keys' => ['shareholder', 'member'],

        'columns' => [
            'name' => ['name', 'company'],
            'ic' => ['ic', 'passport', 'registration', 'nric'],
            'shares' => ['share', 'shares', 'total'],
        ],
    ],

    'bank_accounts' => [
        /*
         * Extracted-field keys (matched against a normalized field_key, same
         * normalization as company.name_keys) that hold each bank-account
         * attribute, in priority order.
         */
        'account_no_keys' => ['account_no', 'account_number', 'acc_no'],

        'account_holder_keys' => ['account_holder_name', 'account_holder', 'account_name', 'company_name'],

        'bank_name_keys' => ['bank_account_product', 'account_product', 'bank_name', 'bank'],

        'account_type_keys' => ['account_type'],
    ],

    'raraxuan' => [
        'document_agent' => env('RARAXUAN_DOCUMENT_AGENT', 'doc-universal-extractor'),
    ],
];
```

- [ ] **Step 2: Write the extractor**

Create `app/Services/Documents/BankAccountExtractor.php`:

```php
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
```

- [ ] **Step 3: Write the extractor test**

Create `tests/Feature/BankAccountExtractorTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\BankAccountExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountExtractorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $fields
     */
    private function bankDocument(User $user, array $fields): Document
    {
        $document = Document::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'bank_account',
        ]);

        foreach ($fields as $key => $value) {
            $document->extractedFields()->create([
                'field_key' => $key,
                'field_label' => $key,
                'value' => $value,
                'status' => 'pending',
            ]);
        }

        return $document->refresh();
    }

    public function test_it_creates_a_bank_account_from_extracted_fields(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $document = $this->bankDocument($user, [
            'account_no' => '262205000947',
            'account_holder_name' => 'AAD CONCEPT SDN BHD',
            'bank_account_product' => 'RHB Reflex Cash Management',
        ]);

        app(BankAccountExtractor::class)->syncFromDocument($document, $company);

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('262205000947', $bankAccount->account_no);
        $this->assertSame('AAD CONCEPT SDN BHD', $bankAccount->account_holder_name);
        $this->assertSame('RHB Reflex Cash Management', $bankAccount->bank_name);
        $this->assertSame($document->id, $bankAccount->source_document_id);
    }

    public function test_two_statements_for_the_same_account_dedupe_into_one_record(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '262205000947', 'account_holder_name' => 'AAD CONCEPT SDN BHD']);
        $second = $this->bankDocument($user, ['account_no' => '262205000947']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $this->assertSame(1, BankAccount::where('company_id', $company->id)->count());

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame($second->id, $bankAccount->source_document_id);
        $this->assertSame('AAD CONCEPT SDN BHD', $bankAccount->account_holder_name);
    }

    public function test_differently_formatted_account_numbers_still_dedupe(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '2622-0500-0947']);
        $second = $this->bankDocument($user, ['account_number' => '262205000947']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $this->assertSame(1, BankAccount::where('company_id', $company->id)->count());
    }

    public function test_it_does_nothing_when_no_account_number_is_present(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $document = $this->bankDocument($user, ['account_holder_name' => 'AAD CONCEPT SDN BHD']);

        app(BankAccountExtractor::class)->syncFromDocument($document, $company);

        $this->assertSame(0, BankAccount::count());
    }

    public function test_backfills_missing_fields_from_a_later_document(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '262205000947']);
        $second = $this->bankDocument($user, ['account_no' => '262205000947', 'bank_account_product' => 'RHB Reflex Cash Management']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('RHB Reflex Cash Management', $bankAccount->bank_name);
    }
}
```

- [ ] **Step 4: Run the test**

Run: `php artisan test tests/Feature/BankAccountExtractorTest.php`
Expected: all 5 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add config/docuvault.php app/Services/Documents/BankAccountExtractor.php tests/Feature/BankAccountExtractorTest.php
git commit -m "feat: add BankAccountExtractor service with config-driven field matching"
```

---

### Task 3: Wire `BankAccountExtractor` into `ProcessDocumentWithRaraxuan`

**Files:**
- Modify: `app/Jobs/ProcessDocumentWithRaraxuan.php`
- Modify: `tests/Feature/ProcessDocumentWithRaraxuanTest.php`

**Interfaces:**
- Consumes: `App\Services\Documents\BankAccountExtractor::syncFromDocument(Document $document, Company $company): void` (Task 2).

- [ ] **Step 1: Add the import**

In `app/Jobs/ProcessDocumentWithRaraxuan.php`, add the import alongside the existing extractor imports:

```php
use App\Services\Documents\DirectorExtractor;
use App\Services\Documents\ShareholderExtractor;
use App\Services\Documents\BankAccountExtractor;
use App\Services\Documents\DocumentClassifier;
```

- [ ] **Step 2: Wire the extractor call**

In the same file, find this block inside `process()`:

```php
            $isSsm = $document->document_type === DocumentType::Ssm->value;
            $company = $classifier->resolveCompany($document, create: $isSsm);

            if ($company !== null && $isSsm) {
                app(DirectorExtractor::class)->syncFromDocument($document, $company);
                app(ShareholderExtractor::class)->syncFromDocument($document, $company);
            }
```

Replace it with:

```php
            $isSsm = $document->document_type === DocumentType::Ssm->value;
            $isBankAccount = $document->document_type === DocumentType::BankAccount->value;
            $company = $classifier->resolveCompany($document, create: $isSsm);

            if ($company !== null && $isSsm) {
                app(DirectorExtractor::class)->syncFromDocument($document, $company);
                app(ShareholderExtractor::class)->syncFromDocument($document, $company);
            }

            if ($company !== null && $isBankAccount) {
                app(BankAccountExtractor::class)->syncFromDocument($document, $company);
            }
```

- [ ] **Step 3: Add the job test**

In `tests/Feature/ProcessDocumentWithRaraxuanTest.php`, add this method immediately after `test_only_ssm_documents_create_a_company` (before `test_processing_failure_marks_document_failed`):

```php
    public function test_bank_account_document_syncs_a_bank_account_when_company_resolved(): void
    {
        config()->set('raraxuan.base_url', 'https://ai.raraxuan.test/api');
        config()->set('raraxuan.api_key', 'rx_test_key');
        config()->set('docuvault.raraxuan.document_agent', 'doc-universal-extractor');

        Storage::fake('local');

        $user = \App\Models\User::factory()->create();

        // Create the company first via an SSM document.
        Storage::disk('local')->put('documents/ssm.pdf', 'bytes');
        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'success' => true,
                'data' => ['result' => json_encode([
                    'extracted_fields' => ['company_name' => 'AAD CONCEPT SDN BHD'],
                    'overall_confidence' => 1.0,
                ])],
            ]),
        ]);
        $ssm = Document::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'ssm',
            'file_disk' => 'local',
            'file_path' => 'documents/ssm.pdf',
            'file_type' => 'application/pdf',
        ]);
        (new ProcessDocumentWithRaraxuan($ssm))->handle();

        // Process a bank-account document for the same company.
        Storage::disk('local')->put('documents/bank.pdf', 'bytes');
        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'success' => true,
                'data' => ['result' => json_encode([
                    'extracted_fields' => [
                        'company_name' => 'AAD CONCEPT SDN BHD',
                        'account_no' => '262205000947',
                    ],
                    'overall_confidence' => 0.98,
                ])],
            ]),
        ]);
        $bank = Document::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'bank_account',
            'file_disk' => 'local',
            'file_path' => 'documents/bank.pdf',
            'file_type' => 'application/pdf',
        ]);
        (new ProcessDocumentWithRaraxuan($bank))->handle();

        $bankAccount = \App\Models\BankAccount::where('account_no', '262205000947')->first();
        $this->assertNotNull($bankAccount);
        $this->assertSame($ssm->company_id, $bankAccount->company_id);
    }
```

- [ ] **Step 4: Run the full job test file**

Run: `php artisan test tests/Feature/ProcessDocumentWithRaraxuanTest.php`
Expected: all tests PASS (the 6 pre-existing tests plus the new one).

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/ProcessDocumentWithRaraxuan.php tests/Feature/ProcessDocumentWithRaraxuanTest.php
git commit -m "feat: sync BankAccount records during bank-account document processing"
```

---

### Task 4: "Bank Accounts" tab on the Company view page

**Files:**
- Create: `resources/views/filament/companies/bank-accounts-tab.blade.php`
- Modify: `app/Filament/Resources/Companies/CompanyResource.php`
- Modify: `tests/Feature/CompanyViewRenderTest.php`

**Interfaces:**
- Consumes: `Company::bankAccounts(): HasMany` (Task 1).

- [ ] **Step 1: Write the blade partial**

Create `resources/views/filament/companies/bank-accounts-tab.blade.php`:

```blade
@php
    /** @var \App\Models\Company $company */
    $company = $getRecord();
    $company->loadMissing('bankAccounts');
@endphp

<div>
    @forelse ($company->bankAccounts as $bankAccount)
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
            <div>
                <span style="font-size:0.9rem;font-weight:600;color:#111827">{{ $bankAccount->account_no }}</span>
                @if ($bankAccount->account_holder_name)
                    <span style="font-size:0.75rem;color:#9ca3af;margin-left:0.5rem">{{ $bankAccount->account_holder_name }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                @if ($bankAccount->bank_name)
                    <x-filament::badge color="success">{{ $bankAccount->bank_name }}</x-filament::badge>
                @endif
                @if ($bankAccount->account_type)
                    <span style="font-size:0.75rem;color:#6b7280">{{ $bankAccount->account_type }}</span>
                @endif
            </div>
        </div>
    @empty
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No bank accounts linked to this company yet.</p>
    @endforelse
</div>
```

- [ ] **Step 2: Add the tab to `CompanyResource`**

In `app/Filament/Resources/Companies/CompanyResource.php`, find the `Tabs::make('company_tabs')` block and add a new tab after the `Shareholders` tab:

```php
                    Tab::make('Shareholders')
                        ->icon(Heroicon::BuildingLibrary)
                        ->schema([
                            ViewEntry::make('shareholders_tab')
                                ->hiddenLabel()
                                ->view('filament.companies.shareholders-tab'),
                        ]),

                    Tab::make('Bank Accounts')
                        ->icon(Heroicon::Banknotes)
                        ->schema([
                            ViewEntry::make('bank_accounts_tab')
                                ->hiddenLabel()
                                ->view('filament.companies.bank-accounts-tab'),
                        ]),
                ]),
```

(`Heroicon::Banknotes` is already used elsewhere in the codebase, e.g. `App\Enums\DocumentType::getIcon()` — no new import needed since `Heroicon` is already imported in this file.)

- [ ] **Step 3: Add the view test**

In `tests/Feature/CompanyViewRenderTest.php`, add this method after `test_company_view_renders_tabbed_json`:

```php
    public function test_company_view_shows_bank_accounts_tab(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::factory()->create(['user_id' => $user->id]);

        \App\Models\BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '262205000947',
            'account_holder_name' => 'AAD CONCEPT SDN BHD',
            'bank_name' => 'RHB Reflex Cash Management',
        ]);

        Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
            ->assertOk()
            ->assertSee('262205000947')
            ->assertSee('RHB Reflex Cash Management');
    }
```

- [ ] **Step 4: Run the test**

Run: `php artisan test tests/Feature/CompanyViewRenderTest.php`
Expected: both tests PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/filament/companies/bank-accounts-tab.blade.php app/Filament/Resources/Companies/CompanyResource.php tests/Feature/CompanyViewRenderTest.php
git commit -m "feat: show a Bank Accounts tab on the company view page"
```

---

### Task 5: Full-suite verification

**Files:** none (verification only)

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: all tests PASS, including the 4 new test files and the modified `ProcessDocumentWithRaraxuanTest.php` / `CompanyViewRenderTest.php`.

- [ ] **Step 2: Run static analysis / linting if configured**

Run: `composer run-script pint -- --test` (if Pint is configured; otherwise `./vendor/bin/pint --test`)
Expected: no style violations. If violations are found, run without `--test` to auto-fix, then re-run the full test suite.
