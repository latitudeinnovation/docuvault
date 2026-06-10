<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentNote>
 */
class DocumentNoteFactory extends Factory
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
            'user_id' => User::factory(),
            'note' => fake()->paragraph(),
        ];
    }
}
