<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentPage>
 */
class DocumentPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'page_no' => fake()->numberBetween(1, 20),
            'image_path' => null,
            'ocr_text' => fake()->paragraph(),
        ];
    }
}
