<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use App\Models\ExtractedField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessDocumentWithRaraxuanTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_document_as_multipart_and_stores_results(): void
    {
        config()->set('raraxuan.base_url', 'https://ai.raraxuan.test/api');
        config()->set('raraxuan.api_key', 'rx_test_key');
        config()->set('docuvault.raraxuan.document_agent', 'doc-universal-extractor');

        Storage::fake('local');
        Storage::disk('local')->put('documents/sample.pdf', 'private document bytes');

        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'success' => true,
                'data' => [
                    'result' => json_encode([
                        'document_type' => 'HLB PRIMEBIZ CURRENT ACCOUNT',
                        'extracted_fields' => [
                            'account_number' => '21200033993',
                            'customer_name' => 'AAD CONCEPT SDN BHD',
                        ],
                        'overall_confidence' => 0.95,
                    ]),
                ],
            ]),
        ]);

        $document = Document::factory()->create([
            'file_disk' => 'local',
            'file_path' => 'documents/sample.pdf',
            'file_type' => 'application/pdf',
            'original_file_name' => 'sample.pdf',
        ]);

        (new ProcessDocumentWithRaraxuan($document))->handle();

        Http::assertSent(function ($request): bool {
            $parts = collect($request->data());

            $template = $parts->firstWhere('name', 'template');
            $filename = $parts->firstWhere('name', 'variables[filename]');
            $file = $parts->firstWhere('name', 'file');

            return $request->url() === 'https://ai.raraxuan.test/api/v1/prompts/process'
                && $request->isMultipart()
                && ($template['contents'] ?? null) === 'doc-universal-extractor'
                && ($filename['contents'] ?? null) === 'sample.pdf'
                && $file !== null
                && (string) ($file['contents'] ?? '') === 'private document bytes';
        });

        $document->refresh();

        $this->assertSame(DocumentStatus::NeedsReview, $document->status);
        $this->assertSame('0.9500', $document->ai_confidence);
        $this->assertNotNull($document->processed_at);

        // Only the marked extracted_fields are surfaced, with clean keys.
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'account_number',
            'field_label' => 'Account Number',
            'value' => '21200033993',
            'status' => ExtractedFieldStatus::Pending->value,
        ]);
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'customer_name',
            'value' => 'AAD CONCEPT SDN BHD',
        ]);

        // Schema envelope keys must NOT become field rows.
        $this->assertDatabaseMissing(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'document_type',
        ]);
        $this->assertSame(2, $document->extractedFields()->count());
    }

    public function test_flattens_every_json_field_and_skips_empty_containers(): void
    {
        config()->set('raraxuan.base_url', 'https://ai.raraxuan.test/api');
        config()->set('raraxuan.api_key', 'rx_test_key');
        config()->set('docuvault.raraxuan.document_agent', 'doc-universal-extractor');

        Storage::fake('local');
        Storage::disk('local')->put('documents/statement.pdf', 'private document bytes');

        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'success' => true,
                'data' => [
                    'result' => json_encode([
                        'total_pages' => 4,
                        'extracted_fields' => [
                            'customer_name' => 'AAD CONCEPT SDN BHD',
                            'transaction_table_headers' => ['Date', 'Description', 'Deposit', 'Withdrawal', 'Balance'],
                        ],
                        'tables' => [],
                        'signatures' => [],
                        'overall_confidence' => 0.95,
                    ]),
                ],
            ]),
        ]);

        $document = Document::factory()->create([
            'file_disk' => 'local',
            'file_path' => 'documents/statement.pdf',
            'file_type' => 'application/pdf',
            'original_file_name' => 'statement.pdf',
        ]);

        (new ProcessDocumentWithRaraxuan($document))->handle();

        $document->refresh();

        $this->assertSame(DocumentStatus::NeedsReview, $document->status);

        // Marked fields surfaced with clean keys; scalar lists joined.
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'customer_name',
            'field_label' => 'Customer Name',
            'value' => 'AAD CONCEPT SDN BHD',
        ]);
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'transaction_table_headers',
            'value' => 'Date, Description, Deposit, Withdrawal, Balance',
        ]);

        // Schema envelope / empty containers must NOT produce rows.
        $this->assertDatabaseMissing(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'total_pages',
        ]);
        $this->assertDatabaseMissing(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'tables',
        ]);
        $this->assertSame(2, $document->extractedFields()->count());
    }

    public function test_processing_failure_marks_document_failed(): void
    {
        Storage::fake('local');

        $document = Document::factory()->create([
            'file_disk' => 'local',
            'file_path' => 'documents/missing.pdf',
        ]);

        (new ProcessDocumentWithRaraxuan($document))->handle();

        $this->assertSame(DocumentStatus::Failed, $document->refresh()->status);
    }
}
