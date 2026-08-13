<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentInfolistJsonViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_document_renders_normalized_extracted_result_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $document = Document::factory()->create([
            'status' => DocumentStatus::NeedsReview,
            'ai_raw_json' => [
                'success' => true,
                'data' => [
                    'result' => 'some raw response',
                ],
                'normalized_result' => [
                    'document_type' => 'ACCOUNT STATEMENT',
                    'extracted_fields' => [
                        'account_no' => '8881051766214',
                        'closing_balance' => '6863.99',
                    ],
                    'overall_confidence' => 0.95,
                ],
            ],
        ]);

        Livewire::test(ViewDocument::class, ['record' => $document->getRouteKey()])
            ->assertSee('8881051766214')
            ->assertSee('ACCOUNT STATEMENT');
    }
}
