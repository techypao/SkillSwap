<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use App\Services\SkillSearchService;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\SkillAliasSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SkillSearchControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SkillCategorySeeder::class, CanonicalSkillSeeder::class, SkillAliasSeeder::class]);
    }

    public function test_guests_cannot_search_skills(): void
    {
        $this->getJson(route('skills.search', ['q' => 'laravel']))
            ->assertUnauthorized();
    }

    public function test_a_user_still_onboarding_can_search(): void
    {
        // The picker is used during onboarding, so the endpoint must not sit
        // behind the completed-onboarding middleware.
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => 'laravel']))
            ->assertOk();
    }

    public function test_it_returns_only_the_fields_the_picker_needs(): void
    {
        $user = User::factory()->onboarded()->create();

        $response = $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => 'larvel']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Laravel')
            ->assertJsonPath('0.category', 'Technology & Programming');

        $this->assertSame(
            ['id', 'name', 'category'],
            array_keys($response->json('0'))
        );
    }

    public function test_alias_queries_return_the_canonical_skill(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => 'JS']))
            ->assertOk()
            ->assertJsonPath('0.name', 'JavaScript');
    }

    public function test_query_is_validated(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => str_repeat('a', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_missing_query_returns_an_empty_list(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->getJson(route('skills.search'))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_results_are_capped_at_the_documented_limit(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(SkillSearchService::RESULT_LIMIT);
    }

    public function test_unapproved_custom_skills_are_not_offered_as_suggestions(): void
    {
        $user = User::factory()->onboarded()->create();
        Skill::create([
            'name' => 'Secret Custom Skill',
            'is_approved' => false,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('skills.search', ['q' => 'Secret Custom Skill']))
            ->assertOk()
            ->assertExactJson([]);
    }
}
