<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ExtractedField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DiagnoseDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_month_tab_and_flags_upload_date_fallback(): void
    {
        $user = User::factory()->create();

        // A statement whose period parses to January 2025.
        $parsed = Document::factory()->for($user, 'owner')->create([
            'title' => 'RHB Jan 2025',
            'document_type' => 'bank_account',
            'status' => DocumentStatus::NeedsReview,
            'processed_at' => now()->setDate(2026, 8, 6),
        ]);
        ExtractedField::factory()->for($parsed)->create([
            'field_key' => 'statement_period',
            'value' => '16 Jan 25 – 31 Jan 25',
        ]);

        // A statement with no parseable period — falls back to the upload month.
        $fallback = Document::factory()->for($user, 'owner')->create([
            'title' => 'RHB Feb 2025',
            'document_type' => 'bank_account',
            'status' => DocumentStatus::Failed,
            'processed_at' => now()->setDate(2026, 8, 6),
        ]);

        $exitCode = Artisan::call('documents:diagnose', ['--search' => 'RHB']);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('January 2025', $output);
        $this->assertStringContainsString('from statement', $output);
        $this->assertStringContainsString('upload-date fallback', $output);
        $this->assertStringContainsString('Grouped under the upload-month tab (period not parsed): 1', $output);
    }
}
