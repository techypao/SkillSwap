<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\SkillAliasSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 6.5 improves how skills are *found*. It must not change what counts as
 * a *match*. Compatibility stays exact canonical skill-id equality.
 */
class MatchingSemanticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SkillCategorySeeder::class, CanonicalSkillSeeder::class, SkillAliasSeeder::class]);
    }

    private function skill(string $name): Skill
    {
        return Skill::where('name', $name)->firstOrFail();
    }

    /**
     * @return array{User, User}
     */
    private function pairTeachingAndLearning(
        string $currentTeaches,
        string $currentLearns,
        string $otherTeaches,
        string $otherLearns
    ): array {
        $currentUser = User::factory()->onboarded()->create();
        $otherUser = User::factory()->onboarded()->create();

        $currentUser->teachingSkills()->attach($this->skill($currentTeaches), ['type' => 'teach']);
        $currentUser->learningSkills()->attach($this->skill($currentLearns), ['type' => 'learn']);
        $otherUser->teachingSkills()->attach($this->skill($otherTeaches), ['type' => 'teach']);
        $otherUser->learningSkills()->attach($this->skill($otherLearns), ['type' => 'learn']);

        return [$currentUser, $otherUser];
    }

    public function test_identical_canonical_skills_are_a_mutual_match(): void
    {
        [$currentUser, $otherUser] = $this->pairTeachingAndLearning(
            'Figma', 'Laravel', 'Laravel', 'Figma'
        );

        $this->actingAs($currentUser)
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('Mutual Match');
    }

    /**
     * Related skills, and skills linked only through an alias family, must not
     * become compatible with one another.
     */
    #[DataProvider('relatedButDistinctSkills')]
    public function test_related_skills_are_not_a_match(string $theyTeach, string $iWantToLearn): void
    {
        [$currentUser, $otherUser] = $this->pairTeachingAndLearning(
            'Figma', $iWantToLearn, $theyTeach, 'Figma'
        );

        $this->actingAs($currentUser)
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('Not a Mutual Match');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function relatedButDistinctSkills(): array
    {
        return [
            'Laravel is not PHP' => ['PHP', 'Laravel'],
            'React is not JavaScript' => ['JavaScript', 'React'],
            'Figma is not UI/UX Design' => ['UI/UX Design', 'Figma'],
            'Java is not JavaScript' => ['JavaScript', 'Java'],
            'C++ is not C' => ['C', 'C++'],
            'C# is not C++' => ['C++', 'C#'],
        ];
    }

    public function test_a_same_named_skill_with_a_different_id_is_not_a_match(): void
    {
        [$currentUser, $otherUser] = $this->pairTeachingAndLearning(
            'Figma', 'Laravel', 'Laravel', 'Figma'
        );

        // Swap their Laravel for a different row that merely shares the label.
        $duplicate = Skill::create(['name' => 'LARAVEL', 'is_approved' => true]);
        $otherUser->teachingSkills()->detach();
        $otherUser->teachingSkills()->attach($duplicate, ['type' => 'teach']);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('Not a Mutual Match');
    }

    public function test_swap_requests_still_require_genuinely_held_canonical_skills(): void
    {
        [$currentUser, $otherUser] = $this->pairTeachingAndLearning(
            'Figma', 'Laravel', 'Laravel', 'Figma'
        );

        // A skill neither party selected cannot be smuggled in as an id.
        $this->actingAs($currentUser)
            ->post(route('swap-requests.store'), [
                'recipient_id' => $otherUser->id,
                'offered_skill_id' => $this->skill('Python')->id,
                'requested_skill_id' => $this->skill('Laravel')->id,
            ])
            ->assertSessionHasErrors('offered_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_a_valid_swap_request_still_succeeds(): void
    {
        [$currentUser, $otherUser] = $this->pairTeachingAndLearning(
            'Figma', 'Laravel', 'Laravel', 'Figma'
        );

        $this->actingAs($currentUser)
            ->post(route('swap-requests.store'), [
                'recipient_id' => $otherUser->id,
                'offered_skill_id' => $this->skill('Figma')->id,
                'requested_skill_id' => $this->skill('Laravel')->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('swap_requests', [
            'sender_id' => $currentUser->id,
            'recipient_id' => $otherUser->id,
            'offered_skill_id' => $this->skill('Figma')->id,
            'requested_skill_id' => $this->skill('Laravel')->id,
        ]);
    }
}
