<?php

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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('account_no');
            $table->string('slug');
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
