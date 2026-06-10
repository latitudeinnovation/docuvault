<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class CoreSchemaAndAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_document_processing_tables_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasTable('document_pages'));
        $this->assertTrue(Schema::hasTable('extracted_fields'));
        $this->assertTrue(Schema::hasTable('document_notes'));

        $this->assertTrue(Schema::hasColumns('documents', [
            'user_id',
            'title',
            'document_type',
            'file_disk',
            'file_path',
            'original_file_name',
            'file_type',
            'status',
            'ai_confidence',
            'ai_raw_json',
            'processed_at',
        ]));

        $this->assertTrue(Schema::hasColumns('document_pages', [
            'document_id',
            'page_no',
            'image_path',
            'ocr_text',
        ]));

        $this->assertTrue(Schema::hasColumns('extracted_fields', [
            'document_id',
            'field_key',
            'field_label',
            'value',
            'confidence',
            'corrected_value',
            'status',
        ]));

        $this->assertTrue(Schema::hasColumns('document_notes', [
            'document_id',
            'user_id',
            'note',
        ]));
    }

    public function test_document_status_values_match_the_documented_lifecycle(): void
    {
        $this->assertSame([
            'uploaded',
            'processing',
            'needs_review',
            'approved',
            'failed',
        ], array_column(DocumentStatus::cases(), 'value'));
    }

    public function test_extracted_field_status_values_match_the_documented_lifecycle(): void
    {
        $this->assertSame([
            'pending',
            'approved',
            'rejected',
        ], array_column(ExtractedFieldStatus::cases(), 'value'));
    }

    public function test_admin_user_seeder_creates_support_login_from_configured_password(): void
    {
        config()->set('admin.user.password', 'correct-horse-battery-staple');

        $this->seed(AdminUserSeeder::class);

        $user = User::query()
            ->where('email', 'support@latitudeinnovation.com.my')
            ->firstOrFail();

        $this->assertSame('Latitude Innovation Support', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
    }

    public function test_admin_user_seeder_fails_without_configured_password(): void
    {
        config()->set('admin.user.password', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_USER_PASSWORD must be set before seeding the admin user.');

        $this->seed(AdminUserSeeder::class);
    }
}
