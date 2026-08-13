<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessDocumentWithRaraxuanCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_processing_job(): void
    {
        Queue::fake([ProcessDocumentWithRaraxuan::class]);

        $document = Document::factory()->create();

        $this->artisan('documents:process-raraxuan', [
            'document' => $document->id,
        ])
            ->expectsOutput("Document [{$document->id}] queued for Raraxuan processing.")
            ->assertSuccessful();

        Queue::assertPushed(ProcessDocumentWithRaraxuan::class, fn (ProcessDocumentWithRaraxuan $job): bool => $job->document->is($document));
    }

    public function test_command_can_process_document_synchronously(): void
    {
        config()->set('raraxuan.base_url', 'https://ai.raraxuan.test/api');
        config()->set('raraxuan.api_key', 'rx_test_key');
        config()->set('docuvault.raraxuan.document_agent', 'doc-universal-extractor');

        Storage::fake('local');
        Storage::disk('local')->put('documents/sample.pdf', 'private document bytes');

        Http::fake([
            'https://ai.raraxuan.test/api/v1/prompts/process' => Http::response([
                'confidence' => 0.91,
                'fields' => [],
            ]),
        ]);

        $document = Document::factory()->create([
            'file_disk' => 'local',
            'file_path' => 'documents/sample.pdf',
            'status' => DocumentStatus::Uploaded,
        ]);

        $this->artisan('documents:process-raraxuan', [
            'document' => $document->id,
            '--sync' => true,
        ])
            ->expectsOutput("Document [{$document->id}] processed with Raraxuan.")
            ->assertSuccessful();

        $this->assertSame(DocumentStatus::NeedsReview, $document->refresh()->status);
    }

    public function test_command_fails_when_document_does_not_exist(): void
    {
        $this->artisan('documents:process-raraxuan', [
            'document' => 999,
        ])
            ->expectsOutput('Document not found.')
            ->assertFailed();
    }
}
