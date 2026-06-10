<?php

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type')->default('general');
            $table->string('file_disk')->default('local');
            $table->string('file_path');
            $table->string('original_file_name')->nullable();
            $table->string('file_type');
            $table->string('status')->default(DocumentStatus::Uploaded->value);
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->json('ai_raw_json')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('document_type');
            $table->index('status');
            $table->index('processed_at');
        });

        Schema::create('document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_no');
            $table->string('image_path')->nullable();
            $table->longText('ocr_text')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'page_no']);
        });

        Schema::create('extracted_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->string('field_label');
            $table->text('value')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('corrected_value')->nullable();
            $table->string('status')->default(ExtractedFieldStatus::Pending->value);
            $table->timestamps();

            $table->index(['document_id', 'status']);
            $table->index('status');
            $table->index('field_key');
        });

        Schema::create('document_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_notes');
        Schema::dropIfExists('extracted_fields');
        Schema::dropIfExists('document_pages');
        Schema::dropIfExists('documents');
    }
};
