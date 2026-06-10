<?php

namespace Database\Factories;

use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
use App\Models\ExtractedField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtractedField>
 */
class ExtractedFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fieldKey = fake()->randomElement(['customer_name', 'invoice_total', 'invoice_date']);

        return [
            'document_id' => Document::factory(),
            'field_key' => $fieldKey,
            'field_label' => str($fieldKey)->replace('_', ' ')->title()->toString(),
            'value' => fake()->words(3, true),
            'confidence' => fake()->randomFloat(4, 0.5, 0.99),
            'corrected_value' => null,
            'status' => ExtractedFieldStatus::Pending,
        ];
    }
}
