<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\ExtractedFields\Pages\ListExtractedFields;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use App\Models\ExtractedField;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_list_pages_load(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListDocuments::class)->assertOk();
        Livewire::test(ListExtractedFields::class)->assertOk();
        Livewire::test(ListUsers::class)->assertOk();
    }

    public function test_document_create_page_uploads_private_file_and_dispatches_processing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('local');
        Queue::fake([ProcessDocumentWithRaraxuan::class]);

        Livewire::test(CreateDocument::class)
            ->set('data.user_id', $user->id)
            ->set('data.title', 'Uploaded invoice')
            ->set('data.document_type', 'invoice')
            ->set('data.file_path', UploadedFile::fake()->create('invoice.pdf', 51200, 'application/pdf'))
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::query()->firstOrFail();

        $this->assertSame($user->id, $document->user_id);
        $this->assertSame('invoice', $document->document_type);
        $this->assertSame('local', $document->file_disk);
        $this->assertSame('invoice.pdf', $document->original_file_name);
        Storage::disk('local')->assertExists($document->file_path);
        Queue::assertPushed(ProcessDocumentWithRaraxuan::class);
    }

    public function test_field_and_document_approval_actions_update_statuses(): void
    {
        $this->actingAs(User::factory()->create());

        $document = Document::factory()->create([
            'status' => DocumentStatus::NeedsReview,
        ]);
        $field = ExtractedField::factory()
            ->for($document)
            ->create([
                'status' => ExtractedFieldStatus::Pending,
            ]);

        Livewire::test(ListExtractedFields::class)
            ->callAction(TestAction::make('approve')->table($field));

        $this->assertSame(ExtractedFieldStatus::Approved, $field->refresh()->status);

        Livewire::test(ListDocuments::class)
            ->callAction(TestAction::make('approve')->table($document));

        $this->assertSame(DocumentStatus::Approved, $document->refresh()->status);
    }
}
