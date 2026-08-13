<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Document;
use App\Models\ExtractedField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillGeneralDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_strips_extraction_results_from_general_documents(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $general = Document::factory()->for($user, 'owner')->create([
            'document_type' => 'general',
            'company_id' => $company->id,
            'status' => DocumentStatus::NeedsReview,
            'ai_raw_json' => ['normalized_result' => ['foo' => 'bar']],
            'ai_confidence' => 0.95,
            'processed_at' => now(),
        ]);
        ExtractedField::factory()->for($general)->create();

        // A non-General document with extraction must be left untouched.
        $bank = Document::factory()->for($user, 'owner')->create([
            'document_type' => 'bank_account',
            'status' => DocumentStatus::NeedsReview,
            'ai_confidence' => 0.9,
        ]);
        ExtractedField::factory()->for($bank)->create();

        $this->artisan('documents:backfill-general', ['--force' => true])
            ->assertSuccessful();

        $general->refresh();
        $this->assertSame(DocumentStatus::Uploaded, $general->status);
        $this->assertNull($general->ai_raw_json);
        $this->assertNull($general->ai_confidence);
        $this->assertNull($general->processed_at);
        $this->assertSame(0, $general->extractedFields()->count());
        // Company association is preserved.
        $this->assertSame($company->id, $general->company_id);

        // Bank statement is untouched.
        $bank->refresh();
        $this->assertSame(DocumentStatus::NeedsReview, $bank->status);
        $this->assertSame(1, $bank->extractedFields()->count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $user = User::factory()->create();
        $general = Document::factory()->for($user, 'owner')->create([
            'document_type' => 'general',
            'status' => DocumentStatus::NeedsReview,
            'ai_confidence' => 0.95,
        ]);
        ExtractedField::factory()->for($general)->create();

        $this->artisan('documents:backfill-general')
            ->assertSuccessful();

        $general->refresh();
        $this->assertSame(DocumentStatus::NeedsReview, $general->status);
        $this->assertSame(1, $general->extractedFields()->count());
    }
}
