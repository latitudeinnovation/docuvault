<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_built_in_types_are_seeded(): void
    {
        $this->assertEqualsCanonicalizing(
            ['ssm', 'bank_account', 'general'],
            DocumentType::pluck('key')->all(),
        );

        $this->assertTrue(DocumentType::where('key', 'ssm')->value('is_system'));
    }

    public function test_select_options_include_all_types_in_order(): void
    {
        $this->assertSame(
            ['ssm' => 'SSM', 'bank_account' => 'Bank Account', 'general' => 'General'],
            DocumentType::selectOptions(),
        );
    }

    public function test_admin_can_create_a_new_document_type_that_becomes_selectable(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListDocumentTypes::class)->assertOk();

        Livewire::test(CreateDocumentType::class)
            ->set('data.label', 'Invoice')
            ->set('data.color', 'warning')
            ->set('data.sort_order', 5)
            ->call('create')
            ->assertHasNoFormErrors();

        // Key and icon are derived from the label, not entered by hand.
        $type = DocumentType::where('label', 'Invoice')->firstOrFail();
        $this->assertSame('invoice', $type->key);
        $this->assertSame('heroicon-o-document-currency-dollar', $type->icon);
        $this->assertFalse($type->is_system);
        $this->assertArrayHasKey('invoice', DocumentType::selectOptions());
    }

    public function test_duplicate_labels_get_distinct_auto_keys(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['invoice', 'invoice_2'] as $expectedKey) {
            Livewire::test(CreateDocumentType::class)
                ->set('data.label', 'Invoice')
                ->set('data.color', 'gray')
                ->set('data.sort_order', 0)
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertTrue(DocumentType::where('key', $expectedKey)->exists());
        }
    }

    public function test_resolve_falls_back_to_the_enum_for_unknown_keys(): void
    {
        $type = DocumentType::resolve('something_removed');

        $this->assertSame('General', $type->label);
        $this->assertSame('gray', $type->color);
        $this->assertSame('heroicon-o-document-text', $type->icon);
    }

    public function test_built_in_types_cannot_be_deleted(): void
    {
        $ssm = DocumentType::where('key', 'ssm')->firstOrFail();

        $this->assertFalse($ssm->delete());
        $this->assertDatabaseHas('document_types', ['key' => 'ssm']);
    }
}
