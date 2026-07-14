<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountNo = fake()->numerify('##########');

        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'account_no' => $accountNo,
            'slug' => BankAccount::slugFor($accountNo),
            'account_holder_name' => fake()->company(),
            'bank_name' => fake()->randomElement(['Maybank', 'RHB', 'HLB PrimeBiz', 'CIMB']),
            'account_type' => fake()->randomElement(['Current Account', 'Savings Account']),
            'currency' => 'MYR',
        ];
    }
}
