<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
use App\Models\DocumentNote;
use App\Models\DocumentPage;
use App\Models\ExtractedField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_relationships_and_casts_are_available(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()
            ->for($user, 'owner')
            ->create([
                'status' => DocumentStatus::NeedsReview,
                'ai_raw_json' => ['confidence' => 0.91],
                'processed_at' => now(),
            ]);

        DocumentPage::factory()->for($document)->create();
        ExtractedField::factory()->for($document)->create([
            'status' => ExtractedFieldStatus::Approved,
        ]);
        DocumentNote::factory()->for($document)->for($user)->create();

        $document->refresh();

        $this->assertSame($user->id, $document->owner->id);
        $this->assertCount(1, $document->pages);
        $this->assertCount(1, $document->extractedFields);
        $this->assertCount(1, $document->notes);
        $this->assertSame(DocumentStatus::NeedsReview, $document->status);
        $this->assertSame(['confidence' => 0.91], $document->ai_raw_json);
        $this->assertSame(ExtractedFieldStatus::Approved, $document->extractedFields->first()->status);
    }
}
