<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolSearchService;
use Database\Seeders\SchoolSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SchoolSearchControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SchoolSeeder::class);
    }

    public function test_guests_cannot_search_schools(): void
    {
        $this->getJson(route('schools.search', ['q' => 'feu']))
            ->assertUnauthorized();
    }

    public function test_a_user_still_onboarding_can_search(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this->actingAs($user)
            ->getJson(route('schools.search', ['q' => 'feu']))
            ->assertOk();
    }

    public function test_it_returns_only_the_fields_the_picker_needs(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('schools.search', ['q' => 'Far Eastern University']))
            ->assertOk()
            ->assertJsonPath('0.name', 'Far Eastern University')
            ->assertJsonPath('0.abbreviation', 'FEU')
            ->assertJsonPath('0.city', 'Manila')
            ->assertJsonPath('0.province', 'Metro Manila');

        $this->assertSame(
            ['id', 'name', 'abbreviation', 'city', 'province'],
            array_keys($response->json('0'))
        );
    }

    public function test_exact_abbreviation_ranks_first_followed_by_name_prefix(): void
    {
        $names = $this->searchNames('FEU');

        $this->assertSame('Far Eastern University', $names[0]);
        $this->assertContains('FEU Institute of Technology', $names);
        $this->assertLessThan(
            array_search('FEU Institute of Technology', $names),
            array_search('Far Eastern University', $names)
        );
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->assertSame($this->searchNames('FEU'), $this->searchNames('fEu'));
        $this->assertContains('University of Santo Tomas', $this->searchNames('SANTO TOMAS'));
    }

    public function test_partial_school_name_returns_relevant_results(): void
    {
        $names = $this->searchNames('far eas');

        $this->assertSame('Far Eastern University', $names[0]);
        $this->assertContains('FEU Institute of Technology', $names);
    }

    public function test_up_abbreviation_prefix_returns_university_of_the_philippines_campuses(): void
    {
        $names = $this->searchNames('UP');

        $this->assertContains('University of the Philippines Diliman', $names);
        $this->assertContains('University of the Philippines Los Baños', $names);
    }

    public function test_exact_school_name_outranks_name_prefix(): void
    {
        School::create(['name' => 'National University Extension Campus']);

        $this->assertSame('National University', $this->searchNames('national university')[0]);
    }

    public function test_like_wildcards_in_the_query_are_literal(): void
    {
        $this->assertSame([], $this->searchNames('%'));
        $this->assertSame([], $this->searchNames('_'));
    }

    public function test_inactive_schools_are_excluded(): void
    {
        School::where('name', 'Far Eastern University')->update(['is_active' => false]);

        $names = $this->searchNames('feu');

        $this->assertNotContains('Far Eastern University', $names);
        $this->assertContains('FEU Institute of Technology', $names);
    }

    public function test_results_are_capped(): void
    {
        foreach (range(1, 15) as $number) {
            School::create(['name' => "Capped Test College {$number}"]);
        }

        $this->actingAs(User::factory()->create())
            ->getJson(route('schools.search', ['q' => 'capped test']))
            ->assertOk()
            ->assertJsonCount(SchoolSearchService::RESULT_LIMIT);
    }

    public function test_query_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('schools.search'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($user)
            ->getJson(route('schools.search', ['q' => str_repeat('a', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($user)
            ->getJson(route('schools.search', ['q' => ['feu']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    /** @return list<string> */
    private function searchNames(string $query): array
    {
        return $this->actingAs(User::factory()->create())
            ->getJson(route('schools.search', ['q' => $query]))
            ->assertOk()
            ->json('*.name');
    }
}
