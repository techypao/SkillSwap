<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DiscoverMatchesOnlyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const UNCHECKED_TOGGLE = 'form="discover-search" aria-label="Show only two-way matches" >';

    private const CHECKED_TOGGLE = 'form="discover-search" aria-label="Show only two-way matches" checked>';

    private Skill $typeScript;

    private Skill $php;

    private Skill $figma;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeScript = Skill::create(['name' => 'TypeScript', 'is_approved' => true]);
        $this->php = Skill::create(['name' => 'PHP', 'is_approved' => true]);
        $this->figma = Skill::create(['name' => 'Figma', 'is_approved' => true]);
    }

    public function test_only_two_way_compatible_users_are_tagged_as_matches(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php]);
        $compatible = $this->userWithSkills('Compatible Carla', teaches: [$this->php], learns: [$this->typeScript]);
        $this->userWithSkills('Teaches Only Tom', teaches: [$this->php], learns: [$this->figma]);
        $this->userWithSkills('Learns Only Lea', teaches: [$this->figma], learns: [$this->typeScript]);
        $this->userWithSkills('Unrelated Uma', teaches: [$this->figma], learns: [$this->figma]);

        $response = $this->actingAs($currentUser)->get(route('discover.index'));

        $response->assertOk()
            ->assertViewHas('matchCount', 1)
            ->assertSee('1 match found');
        $this->assertSame([$compatible->id], $this->matchedUserIds($response));
        $this->assertSame(1, substr_count($response->getContent(), 'class="user-card is-match"'));
        $this->assertSame(3, substr_count($response->getContent(), 'class="user-card"'));
    }

    public function test_matched_user_card_shows_shared_skills_without_percentage(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php, $this->figma]);
        $this->userWithSkills('Compatible Carla', teaches: [$this->php], learns: [$this->typeScript]);

        $this->actingAs($currentUser)
            ->get(route('discover.index'))
            ->assertOk()
            ->assertSeeInOrder(['You Can Learn', 'PHP', 'You Can Teach', 'TypeScript'])
            ->assertDontSee('75%')
            ->assertSee('No reviews yet');
    }

    public function test_current_admin_and_incomplete_users_never_appear(): void
    {
        $currentUser = $this->userWithSkills('Current Self', teaches: [$this->typeScript, $this->php], learns: [$this->php, $this->typeScript]);
        $admin = User::factory()->admin()->create(['name' => 'Admin Adam', 'onboarding_completed' => true]);
        $incomplete = User::factory()->create(['name' => 'Incomplete Ivy', 'onboarding_completed' => false]);

        foreach ([$admin, $incomplete] as $user) {
            $user->teachingSkills()->attach($this->php, ['type' => 'teach']);
            $user->learningSkills()->attach($this->typeScript, ['type' => 'learn']);
        }

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['mode' => 'matches']))
            ->assertOk()
            ->assertViewHas('users', fn (Collection $users): bool => $users->isEmpty())
            ->assertViewHas('matchCount', 0)
            ->assertDontSee('Admin Adam')
            ->assertDontSee('Incomplete Ivy')
            ->assertDontSee('<h3 class="user-name">', false);
    }

    public function test_match_count_reflects_every_compatible_user(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php]);
        $this->userWithSkills('Match One', teaches: [$this->php], learns: [$this->typeScript]);
        $this->userWithSkills('Match Two', teaches: [$this->php, $this->figma], learns: [$this->typeScript]);
        $this->userWithSkills('Match Three', teaches: [$this->php], learns: [$this->typeScript, $this->figma]);
        $this->userWithSkills('Not A Match', teaches: [$this->php], learns: [$this->figma]);

        $this->actingAs($currentUser)
            ->get(route('discover.index'))
            ->assertViewHas('matchCount', 3)
            ->assertSee('3 matches found')
            ->assertSee('<span class="when-matches">3 matches</span>', false)
            ->assertSee('<span class="when-everyone">4 users</span>', false);
    }

    public function test_empty_state_explains_no_two_way_matches_and_offers_show_all(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php]);
        $this->userWithSkills('One Way Olga', teaches: [$this->php], learns: [$this->figma]);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['mode' => 'matches']))
            ->assertOk()
            ->assertSee('class="card empty-state matches-empty"', false)
            ->assertSee('No two-way matches found')
            ->assertSeeInOrder(['matches-empty', 'for="matches-toggle"', 'Show All'], false);
    }

    public function test_check_match_toggles_in_place_without_navigating(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php]);
        $this->userWithSkills('Compatible Carla', teaches: [$this->php], learns: [$this->typeScript]);
        $this->userWithSkills('One Way Olga', teaches: [$this->php], learns: [$this->figma]);

        $this->actingAs($currentUser)
            ->get(route('discover.index'))
            ->assertOk()
            ->assertViewHas('matchesOnly', false)
            ->assertViewHas('users', fn (Collection $users): bool => $users->count() === 2)
            ->assertSee(self::UNCHECKED_TOGGLE, false)
            ->assertSeeInOrder(['for="matches-toggle"', 'Check Match', 'Show All'], false)
            ->assertSee(':checked ~ .user-list .user-card:not(.is-match)', false)
            ->assertDontSee('mode=matches', false)
            ->assertDontSee('<script', false)
            ->assertSee('Compatible Carla')
            ->assertSee('One Way Olga');
    }

    public function test_matches_mode_url_starts_toggled_on_and_combines_with_search(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript, $this->figma], learns: [$this->php, $this->figma]);
        $phpMatch = $this->userWithSkills('PHP Match', teaches: [$this->php], learns: [$this->typeScript]);
        $this->userWithSkills('Figma Match', teaches: [$this->figma], learns: [$this->figma]);

        $response = $this->actingAs($currentUser)
            ->get(route('discover.index', ['mode' => 'matches', 'search' => 'PHP', 'type' => 'teach']));

        $response->assertOk()
            ->assertViewHas('matchesOnly', true)
            ->assertSee(self::CHECKED_TOGGLE, false)
            ->assertSee('id="discover-search"', false)
            ->assertSee('PHP Match')
            ->assertDontSee('Figma Match')
            ->assertSee('1 match found');
        $this->assertSame([$phpMatch->id], $this->matchedUserIds($response));
    }

    public function test_unknown_mode_starts_with_everyone_visible(): void
    {
        $currentUser = $this->userWithSkills('Current', teaches: [$this->typeScript], learns: [$this->php]);
        $this->userWithSkills('One Way Olga', teaches: [$this->php], learns: [$this->figma]);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['mode' => 'swipe']))
            ->assertOk()
            ->assertViewHas('matchesOnly', false)
            ->assertSee(self::UNCHECKED_TOGGLE, false)
            ->assertSee('One Way Olga');
    }

    /**
     * @return list<int>
     */
    private function matchedUserIds(TestResponse $response): array
    {
        return collect($response->viewData('compatibilities'))
            ->filter(fn (array $compatibility): bool => $compatibility['isMutualMatch'])
            ->keys()
            ->all();
    }

    /**
     * @param  list<Skill>  $teaches
     * @param  list<Skill>  $learns
     */
    private function userWithSkills(string $name, array $teaches, array $learns): User
    {
        $user = User::factory()->onboarded()->create(['name' => $name]);

        foreach ($teaches as $skill) {
            $user->teachingSkills()->attach($skill, ['type' => 'teach']);
        }

        foreach ($learns as $skill) {
            $user->learningSkills()->attach($skill, ['type' => 'learn']);
        }

        return $user;
    }
}
