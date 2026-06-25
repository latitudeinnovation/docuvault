<?php

namespace Tests\Feature;

use App\Filament\Resources\Directors\Pages\ListDirectors;
use App\Filament\Resources\Directors\Pages\ViewDirector;
use App\Models\Company;
use App\Models\Director;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\DirectorExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DirectorExtractorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function ssmDocument(User $user, array $rows): Document
    {
        return Document::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'ssm',
            'ai_raw_json' => ['normalized_result' => [
                'document_type' => 'Company Profile',
                'tables' => [['table_name' => 'Directors / Officers', 'data' => $rows]],
            ]],
        ]);
    }

    public function test_it_parses_officers_table_into_directors_with_pivot_data(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $document = $this->ssmDocument($user, [
            ['Name/Address' => "WOH SAU HAA\nNO.179, TAMAN MELEWAR\n27500 RAUB", 'IC/Passport' => '661024-06-5297', 'Designation' => 'DIRECTOR', 'Date Of Appointment' => '24-05-2018'],
            ['Name/Address' => "CHONG YEW NGEE\n18-9-5, MIDAH RIA", 'IC/Passport' => '671117-08-5237', 'Designation' => 'SECRETARY', 'Date Of Appointment' => '13-09-2021'],
        ]);

        app(DirectorExtractor::class)->syncFromDocument($document, $company);

        // Only the DIRECTOR row is stored; the SECRETARY row is skipped.
        $this->assertSame(1, $company->directors()->count());
        $this->assertTrue(Director::where('name', 'CHONG YEW NGEE')->doesntExist());

        $woh = Director::where('name', 'WOH SAU HAA')->firstOrFail();
        $this->assertSame('661024-06-5297', $woh->ic_passport);
        $this->assertSame('NO.179, TAMAN MELEWAR, 27500 RAUB', $woh->address);

        $pivot = $company->directors()->where('directors.id', $woh->id)->first()->pivot;
        $this->assertSame('DIRECTOR', $pivot->designation);
        $this->assertStringStartsWith('2018-05-24', (string) $pivot->appointed_at);
    }

    public function test_same_ic_links_one_director_to_multiple_companies(): void
    {
        $user = User::factory()->create();
        $companyA = Company::factory()->create(['user_id' => $user->id]);
        $companyB = Company::factory()->create(['user_id' => $user->id]);

        $row = [['Name/Address' => 'WOH SAU HAA', 'IC/Passport' => '661024-06-5297', 'Designation' => 'DIRECTOR', 'Date Of Appointment' => '24-05-2018']];

        app(DirectorExtractor::class)->syncFromDocument($this->ssmDocument($user, $row), $companyA);
        app(DirectorExtractor::class)->syncFromDocument($this->ssmDocument($user, $row), $companyB);

        $this->assertSame(1, Director::count());
        $this->assertSame(2, Director::first()->companies()->count());
    }

    public function test_director_view_lists_company_and_bank_statements(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::factory()->create(['user_id' => $user->id, 'name' => 'AAD CONCEPT SDN. BHD.']);
        app(DirectorExtractor::class)->syncFromDocument(
            $this->ssmDocument($user, [['Name/Address' => 'WOH SAU HAA', 'IC/Passport' => '661024-06-5297', 'Designation' => 'DIRECTOR', 'Date Of Appointment' => '24-05-2018']]),
            $company,
        );

        // A bank statement held under the director's personal name.
        $statement = Document::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'document_type' => 'bank_account', 'title' => 'Personal Statement WOH SAU HAA']);
        $statement->extractedFields()->create(['field_key' => 'account_holder_name', 'field_label' => 'Account Holder Name', 'value' => 'WOH SAU HAA', 'status' => 'pending']);

        // A company statement (holder = company) must NOT show under the director.
        $company->documents()->save(Document::factory()->make(['user_id' => $user->id, 'document_type' => 'bank_account', 'title' => 'Company Statement AAD']));

        $director = Director::firstOrFail();

        Livewire::test(ListDirectors::class)->assertOk()->assertSee('WOH SAU HAA');

        Livewire::test(ViewDirector::class, ['record' => $director->getKey()])
            ->assertOk()
            ->assertSee('AAD CONCEPT SDN. BHD.')      // company he directs
            ->assertSee('Director')
            ->assertSee('Personal Statement WOH SAU HAA') // bank under his name
            ->assertSee('Account Holder Name')
            ->assertDontSee('Company Statement AAD');  // not under his name
    }
}
