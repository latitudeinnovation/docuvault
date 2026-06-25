<?php

namespace Database\Factories;

use App\Models\Director;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Director>
 */
class DirectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'ic_passport' => fake()->numerify('######-##-####'),
            'slug' => Str::slug($name),
            'address' => fake()->address(),
        ];
    }
}
