<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_account_belongs_to_company_and_is_listed_on_it(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '262205000947',
        ]);

        $this->assertTrue($company->bankAccounts->contains($bankAccount));
        $this->assertSame($company->id, $bankAccount->company->id);
    }

    public function test_slug_for_normalizes_to_digits_only(): void
    {
        $this->assertSame('262205000947', BankAccount::slugFor('262205000947'));
        $this->assertSame('262205000947', BankAccount::slugFor('2622-0500-0947'));
    }

    public function test_company_and_slug_are_unique_together(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '111',
            'slug' => BankAccount::slugFor('111'),
        ]);

        $this->expectException(QueryException::class);

        BankAccount::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'account_no' => '111',
            'slug' => BankAccount::slugFor('111'),
        ]);
    }
}
