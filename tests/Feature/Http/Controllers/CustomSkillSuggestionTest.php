<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\SkillAliasSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A custom suggestion must never quietly become a second copy of a skill that
 * already exists under another name, but genuinely new skills must stay
 * suggestable.
 */
class CustomSkillSuggestionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SkillCategorySeeder::class, CanonicalSkillSeeder::class, SkillAliasSeeder::class]);
    }

    private function suggest(User $user, string $name): TestResponse
    {
        return $this->actingAs($user)->post(route('onboarding.skills.teach.store'), [
            'new_teaching_skills' => $name,
        ]);
    }

    #[DataProvider('duplicateSuggestions')]
    public function test_suggestions_that_duplicate_an_existing_skill_are_redirected(
        string $suggestion,
        string $canonical
    ): void {
        $user = User::factory()->create();
        $skillCountBefore = Skill::count();

        $this->suggest($user, $suggestion)
            ->assertSessionHasErrors('new_teaching_skills');

        $this->assertSame(
            $skillCountBefore,
            Skill::count(),
            "[{$suggestion}] created a duplicate skill row."
        );

        $this->assertStringContainsString(
            $canonical,
            session('errors')->first('new_teaching_skills')
        );

        $this->assertDatabaseCount('user_skills', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function duplicateSuggestions(): array
    {
        return [
            'exact alias' => ['Photoshop', 'Adobe Photoshop'],
            'abbreviated alias' => ['MS Excel', 'Microsoft Excel'],
            'short alias' => ['PPT', 'Microsoft PowerPoint'],
            'strong typo' => ['larvel', 'Laravel'],
            'transposed typo' => ['pyhton', 'Python'],
        ];
    }

    #[DataProvider('canonicalNameVariants')]
    public function test_exact_canonical_names_are_reused_rather_than_duplicated(string $suggestion): void
    {
        $user = User::factory()->create();
        $laravel = Skill::where('name', 'Laravel')->firstOrFail();
        $skillCountBefore = Skill::count();

        $this->suggest($user, $suggestion)->assertSessionHasNoErrors();

        $this->assertSame($skillCountBefore, Skill::count());
        $this->assertSame('Laravel', $laravel->fresh()->name);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function canonicalNameVariants(): array
    {
        return [
            'exact' => ['Laravel'],
            'lowercase' => ['laravel'],
            'uppercase' => ['LARAVEL'],
            'padded' => ['  Laravel  '],
        ];
    }

    #[DataProvider('legitimateNewSkills')]
    public function test_genuinely_new_skills_can_still_be_suggested(string $name): void
    {
        $user = User::factory()->create();

        $this->suggest($user, $name)->assertSessionHasNoErrors();

        $created = Skill::where('name', trim($name))->first();

        $this->assertNotNull($created, "[{$name}] should have been created.");
        $this->assertFalse($created->is_approved);
        $this->assertSame($user->id, $created->created_by);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $created->id,
            'type' => 'teach',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function legitimateNewSkills(): array
    {
        return [
            'Blender' => ['Blender'],
            'Crocheting' => ['Crocheting'],
            'Campus Event Hosting' => ['Campus Event Hosting'],
        ];
    }

    public function test_a_weak_similarity_does_not_block_a_new_skill(): void
    {
        $user = User::factory()->create();

        // "Blender" shares letters with several catalog entries but is not a
        // near-certain typo of any of them, so it must not be blocked.
        $this->suggest($user, 'Blender')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skills', [
            'name' => 'Blender',
            'is_approved' => false,
            'created_by' => $user->id,
        ]);
    }

    public function test_one_bad_suggestion_blocks_the_whole_submission(): void
    {
        $user = User::factory()->create();
        $skillCountBefore = Skill::count();

        $this->suggest($user, 'Blender, Photoshop')
            ->assertSessionHasErrors('new_teaching_skills');

        // Nothing is written when any suggestion needs correcting.
        $this->assertSame($skillCountBefore, Skill::count());
        $this->assertDatabaseMissing('skills', ['name' => 'Blender']);
    }
}
