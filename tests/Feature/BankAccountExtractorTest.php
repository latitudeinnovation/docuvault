<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\BankAccountExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountExtractorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $fields
     */
    private function bankDocument(User $user, array $fields): Document
    {
        $document = Document::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'bank_account',
        ]);

        foreach ($fields as $key => $value) {
            $document->extractedFields()->create([
                'field_key' => $key,
                'field_label' => $key,
                'value' => $value,
                'status' => 'pending',
            ]);
        }

        return $document->refresh();
    }

    public function test_it_creates_a_bank_account_from_extracted_fields(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $document = $this->bankDocument($user, [
            'account_no' => '262205000947',
            'account_holder_name' => 'AAD CONCEPT SDN BHD',
            'bank_account_product' => 'RHB Reflex Cash Management',
        ]);

        app(BankAccountExtractor::class)->syncFromDocument($document, $company);

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('262205000947', $bankAccount->account_no);
        $this->assertSame('AAD CONCEPT SDN BHD', $bankAccount->account_holder_name);
        $this->assertSame('RHB Reflex Cash Management', $bankAccount->bank_name);
        $this->assertSame($document->id, $bankAccount->source_document_id);
    }

    public function test_two_statements_for_the_same_account_dedupe_into_one_record(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '262205000947', 'account_holder_name' => 'AAD CONCEPT SDN BHD']);
        $second = $this->bankDocument($user, ['account_no' => '262205000947']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $this->assertSame(1, BankAccount::where('company_id', $company->id)->count());

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame($second->id, $bankAccount->source_document_id);
        $this->assertSame('AAD CONCEPT SDN BHD', $bankAccount->account_holder_name);
    }

    public function test_differently_formatted_account_numbers_still_dedupe(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '2622-0500-0947']);
        $second = $this->bankDocument($user, ['account_number' => '262205000947']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $this->assertSame(1, BankAccount::where('company_id', $company->id)->count());
    }

    public function test_it_does_nothing_when_no_account_number_is_present(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $document = $this->bankDocument($user, ['account_holder_name' => 'AAD CONCEPT SDN BHD']);

        app(BankAccountExtractor::class)->syncFromDocument($document, $company);

        $this->assertSame(0, BankAccount::count());
    }

    public function test_backfills_missing_fields_from_a_later_document(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $first = $this->bankDocument($user, ['account_no' => '262205000947']);
        $second = $this->bankDocument($user, ['account_no' => '262205000947', 'bank_account_product' => 'RHB Reflex Cash Management']);

        app(BankAccountExtractor::class)->syncFromDocument($first, $company);
        app(BankAccountExtractor::class)->syncFromDocument($second, $company);

        $bankAccount = BankAccount::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('RHB Reflex Cash Management', $bankAccount->bank_name);
    }
}
