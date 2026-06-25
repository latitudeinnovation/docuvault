<?php

namespace Database\Factories;

use App\Models\Shareholder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shareholder>
 */
class ShareholderFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'ic_passport' => fake()->numerify('######-##-####'),
            'slug' => Str::slug($name),
        ];
    }
}
