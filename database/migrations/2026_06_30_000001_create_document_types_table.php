<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            // The value stored on documents.document_type (a plain string, not a
            // DB enum) — the in-code App\Enums\DocumentType holds the built-ins.
            $table->string('key')->unique();
            $table->string('label');
            $table->string('color')->default('gray');
            $table->string('icon')->default('heroicon-o-document-text');
            // System rows back the built-in enum cases and cannot be deleted.
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('document_types')->insert([
            [
                'key' => 'ssm',
                'label' => 'SSM',
                'color' => 'info',
                'icon' => 'heroicon-o-building-office-2',
                'is_system' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bank_account',
                'label' => 'Bank Account',
                'color' => 'success',
                'icon' => 'heroicon-o-banknotes',
                'is_system' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'general',
                'label' => 'General',
                'color' => 'gray',
                'icon' => 'heroicon-o-document-text',
                'is_system' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
