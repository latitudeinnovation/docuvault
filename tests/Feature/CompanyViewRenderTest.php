<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyViewRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_view_renders_tabbed_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::factory()->create(['user_id' => $user->id]);

        $ssm = Document::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'document_type' => 'ssm',
            'status' => DocumentStatus::NeedsReview,
            'ai_raw_json' => ['normalized_result' => [
                'document_type' => 'Company Profile',
                'extracted_fields' => ['company_name' => ['value' => 'AAD CONCEPT SDN. BHD.', 'confidence' => 0.98]],
            ]],
        ]);
        $ssm->extractedFields()->create([
            'field_key' => 'company_name', 'field_label' => 'Company Name',
            'value' => 'AAD CONCEPT SDN. BHD.', 'status' => 'pending',
        ]);

        $feb = Document::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'document_type' => 'bank_account',
            'status' => DocumentStatus::NeedsReview,
            'ai_raw_json' => ['normalized_result' => ['document_type' => 'Bank Statement']],
        ]);
        $feb->extractedFields()->create([
            'field_key' => 'statement_period_start', 'field_label' => 'Statement Period Start',
            'value' => '01 February 2024', 'status' => 'pending',
        ]);
        $feb->extractedFields()->create([
            'field_key' => 'account_holder_name', 'field_label' => 'Account Holder Name',
            'value' => 'AAD CONCEPT SDN BHD', 'status' => 'pending',
        ]);

        $may = Document::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'document_type' => 'bank_account',
            'status' => DocumentStatus::NeedsReview,
            'ai_raw_json' => ['normalized_result' => ['document_type' => 'Bank Statement']],
        ]);
        $may->extractedFields()->create([
            'field_key' => 'statement_period_start', 'field_label' => 'Statement Period Start',
            'value' => '01 May 2024', 'status' => 'pending',
        ]);

        Livewire::test(ListCompanies::class)->assertOk();

        Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
            ->assertOk()
            ->assertSee('AAD CONCEPT SDN. BHD.')
            ->assertSee('SSM')
            ->assertSee('Bank Account')
            ->assertSee('February 2024')   // month sub-navigation
            ->assertSee('May 2024')
            ->assertSee('Account Holder Name'); // readable label
    }
}
