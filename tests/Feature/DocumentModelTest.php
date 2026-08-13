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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function periodDateProvider(): iterable
    {
        yield 'day-first slash notation' => ['31/12/2023', '2023-12-31'];
        yield 'day-first slash notation, leap day' => ['29/02/2024', '2024-02-29'];
        yield 'natural language date' => ['29 February 2024', '2024-02-29'];
        yield 'date range takes start date' => ['01 February 2024 To 29 February 2024', '2024-02-01'];
    }

    #[DataProvider('periodDateProvider')]
    public function test_period_date_parses_extracted_document_date(string $rawValue, string $expectedDate): void
    {
        $user = User::factory()->create();
        $document = Document::factory()
            ->for($user, 'owner')
            ->create(['processed_at' => now()]);

        ExtractedField::factory()->for($document)->create([
            'field_key' => 'document_date',
            'value' => $rawValue,
        ]);

        $document->refresh();

        $this->assertSame($expectedDate, $document->periodDate()?->toDateString());
    }

    public function test_period_date_falls_back_to_processed_at_when_unparseable(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()
            ->for($user, 'owner')
            ->create(['processed_at' => now()->setDate(2025, 1, 1)]);

        ExtractedField::factory()->for($document)->create([
            'field_key' => 'document_date',
            'value' => 'not a date',
        ]);

        $document->refresh();

        $this->assertSame('2025-01-01', $document->periodDate()?->toDateString());
    }
}
