<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ic_passport')->nullable();
            $table->string('slug');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('company_shareholder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained()->cascadeOnDelete();
            $table->string('shares')->nullable();
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'shareholder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_shareholder');
        Schema::dropIfExists('shareholders');
    }
};
