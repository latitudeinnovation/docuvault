<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\DocumentClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentClassifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_company_creates_company_and_fills_registration_number(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->create(['user_id' => $user->id, 'company_id' => null]);
        $document->extractedFields()->createMany([
            ['field_key' => 'company_name', 'field_label' => 'Company Name', 'value' => 'AAD CONCEPT SDN. BHD.', 'status' => 'pending'],
            ['field_key' => 'registration_no', 'field_label' => 'Registration No', 'value' => '201701033484 (1247655-A)', 'status' => 'pending'],
        ]);

        $company = app(DocumentClassifier::class)->resolveCompany($document->fresh('extractedFields'));

        $this->assertNotNull($company);
        $this->assertSame('AAD CONCEPT SDN. BHD.', $company->name);
        $this->assertSame('201701033484 (1247655-A)', $company->registration_no);
    }

    public function test_resolve_company_handles_bahasa_melayu_field_labels(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->create(['user_id' => $user->id, 'company_id' => null]);
        $document->extractedFields()->createMany([
            ['field_key' => 'Nama Perniagaan', 'field_label' => 'Nama Perniagaan', 'value' => 'KEDAI RUNCIT MAJU', 'status' => 'pending'],
            ['field_key' => 'No. Pendaftaran', 'field_label' => 'No. Pendaftaran', 'value' => '202301099999 (1599999-X)', 'status' => 'pending'],
        ]);

        $company = app(DocumentClassifier::class)->resolveCompany($document->fresh('extractedFields'));

        $this->assertNotNull($company);
        $this->assertSame('KEDAI RUNCIT MAJU', $company->name);
        $this->assertSame('202301099999 (1599999-X)', $company->registration_no);
    }

    public function test_resolve_company_does_not_overwrite_existing_registration_number(): void
    {
        $user = User::factory()->create();

        // First document establishes the registration number.
        $first = Document::factory()->create(['user_id' => $user->id]);
        $first->extractedFields()->createMany([
            ['field_key' => 'company_name', 'field_label' => 'Company Name', 'value' => 'AAD CONCEPT SDN. BHD.', 'status' => 'pending'],
            ['field_key' => 'registration_no', 'field_label' => 'Registration No', 'value' => 'ORIGINAL-REG', 'status' => 'pending'],
        ]);
        app(DocumentClassifier::class)->resolveCompany($first->fresh('extractedFields'));

        // A later document for the same company carries a different number; it must not clobber.
        $second = Document::factory()->create(['user_id' => $user->id]);
        $second->extractedFields()->createMany([
            ['field_key' => 'company_name', 'field_label' => 'Company Name', 'value' => 'AAD CONCEPT SDN. BHD.', 'status' => 'pending'],
            ['field_key' => 'registration_no', 'field_label' => 'Registration No', 'value' => 'DIFFERENT-REG', 'status' => 'pending'],
        ]);
        $company = app(DocumentClassifier::class)->resolveCompany($second->fresh('extractedFields'));

        $this->assertSame('ORIGINAL-REG', $company->registration_no);
    }

    public function test_resolve_company_falls_back_to_bank_account_number_when_name_does_not_match(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id, 'name' => 'AAD CONCEPT SDN BHD']);
        BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '26220500009447',
            'slug' => BankAccount::slugFor('26220500009447'),
        ]);

        // OCR on this statement read the printed company name slightly differently,
        // so it won't slug-match the company above, but the account number matches.
        $document = Document::factory()->create(['user_id' => $user->id, 'company_id' => null]);
        $document->extractedFields()->createMany([
            ['field_key' => 'company_name', 'field_label' => 'Company Name', 'value' => 'AAD CONCEPT SON BHD', 'status' => 'pending'],
            ['field_key' => 'account_no', 'field_label' => 'Account Number', 'value' => '2622-0500-009447', 'status' => 'pending'],
        ]);

        $resolved = app(DocumentClassifier::class)->resolveCompany($document->fresh('extractedFields'), create: false);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($company));
    }

    public function test_resolve_company_returns_null_when_neither_name_nor_account_match(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->create(['user_id' => $user->id, 'company_id' => null]);
        $document->extractedFields()->createMany([
            ['field_key' => 'company_name', 'field_label' => 'Company Name', 'value' => 'UNKNOWN ENTITY SDN BHD', 'status' => 'pending'],
            ['field_key' => 'account_no', 'field_label' => 'Account Number', 'value' => '999999999', 'status' => 'pending'],
        ]);

        $resolved = app(DocumentClassifier::class)->resolveCompany($document->fresh('extractedFields'), create: false);

        $this->assertNull($resolved);
    }
}
