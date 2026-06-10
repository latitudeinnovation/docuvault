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

    public function test_successful_processing_stores_raw_json_fields_and_filename_payload(): void
    {
        config()->set('raraxuan.base_url', 'https://ai.raraxuan.test/api');
        config()->set('raraxuan.api_key', 'rx_test_key');
        config()->set('docuvault.raraxuan.document_agent', 'doc-universal-extractor');

        Storage::fake('local');
        Storage::disk('local')->put('documents/sample.pdf', 'private document bytes');

        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'confidence' => 0.91,
                'fields' => [
                    [
                        'key' => 'customer_name',
                        'label' => 'Customer Name',
                        'value' => 'ABC Sdn Bhd',
                        'confidence' => 0.95,
                    ],
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

        Http::assertSent(fn ($request): bool => $request->url() === 'https://ai.raraxuan.test/api/v1/prompts/process'
            && $request['template'] === 'doc-universal-extractor'
            && $request['variables']['filename'] === 'sample.pdf'
            && count($request['variables']) === 1
            && ! array_key_exists('document_id', $request['variables'])
            && ! array_key_exists('file_base64', $request['variables'])
            && ! array_key_exists('instructions', $request['variables'])
            && ! array_key_exists('file_url', $request['variables']));

        $document->refresh();

        $this->assertSame(DocumentStatus::NeedsReview, $document->status);
        $this->assertSame('0.9100', $document->ai_confidence);
        $this->assertSame(0.91, $document->ai_raw_json['confidence']);
        $this->assertNotNull($document->processed_at);
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'customer_name',
            'field_label' => 'Customer Name',
            'value' => 'ABC Sdn Bhd',
            'status' => ExtractedFieldStatus::Pending->value,
        ]);
    }

    public function test_successful_processing_extracts_fields_from_nested_plugin_result(): void
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
                        'document_type' => 'Bank Account Statement',
                        'fields' => [
                            'account_number' => [
                                'value' => '123-456-7890',
                                'confidence' => 0.99,
                            ],
                            'currency' => [
                                'value' => 'MYR',
                                'confidence' => 0.98,
                            ],
                        ],
                        'overall_confidence' => 0.97,
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
        $this->assertSame('0.9700', $document->ai_confidence);
        $this->assertSame('Bank Account Statement', $document->ai_raw_json['normalized_result']['document_type']);
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'account_number',
            'field_label' => 'Account Number',
            'value' => '123-456-7890',
            'confidence' => '0.9900',
            'status' => ExtractedFieldStatus::Pending->value,
        ]);
        $this->assertDatabaseHas(ExtractedField::class, [
            'document_id' => $document->id,
            'field_key' => 'currency',
            'field_label' => 'Currency',
            'value' => 'MYR',
            'confidence' => '0.9800',
            'status' => ExtractedFieldStatus::Pending->value,
        ]);
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
