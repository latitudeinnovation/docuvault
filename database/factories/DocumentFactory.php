<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'document_type' => 'general',
            'file_disk' => 'local',
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'original_file_name' => fake()->slug().'.pdf',
            'file_type' => 'application/pdf',
            'status' => DocumentStatus::Uploaded,
            'ai_confidence' => null,
            'ai_raw_json' => null,
            'processed_at' => null,
        ];
    }
}
