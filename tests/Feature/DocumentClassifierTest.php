<?php

namespace Tests\Feature;

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
}
