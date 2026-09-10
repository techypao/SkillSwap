<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_user_can_select_an_active_program_and_year_level(): void
    {
        $user = User::factory()->create();
        $program = Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), $this->profilePayload($program, 4))
            ->assertRedirect(route('onboarding.skills'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'program_id' => $program->id,
            'year_level' => 4,
            'school_organization' => 'FEU Institute of Technology',
        ]);
    }

    public function test_invalid_and_inactive_programs_are_rejected(): void
    {
        $user = User::factory()->create();
        $inactiveProgram = Program::create([
            'name' => 'Inactive Program',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), $this->profilePayload($inactiveProgram, 4))
            ->assertSessionHasErrors('program_id');
        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [
                ...$this->profilePayload($inactiveProgram, 4),
                'program_id' => 999999,
            ])
            ->assertSessionHasErrors('program_id');

        $this->assertNull($user->fresh()->program_id);
    }

    #[DataProvider('validYearLevels')]
    public function test_valid_year_levels_are_accepted(int $yearLevel): void
    {
        $user = User::factory()->create();
        $program = Program::create(['name' => 'Program '.$yearLevel, 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), $this->profilePayload($program, $yearLevel))
            ->assertSessionHasNoErrors();

        $this->assertSame($yearLevel, $user->fresh()->year_level);
    }

    public function test_invalid_year_level_is_rejected(): void
    {
        $user = User::factory()->create();
        $program = Program::create(['name' => 'Valid Program', 'is_active' => true]);

        foreach ([0, 6, 'fourth'] as $invalidYearLevel) {
            $this->actingAs($user)
                ->post(route('onboarding.profile.store'), $this->profilePayload($program, $invalidYearLevel))
                ->assertSessionHasErrors('year_level');
        }

        $this->assertNull($user->fresh()->year_level);
    }

    public function test_existing_onboarded_user_with_null_academic_fields_remains_usable(): void
    {
        $user = User::factory()->onboarded()->create([
            'program_id' => null,
            'year_level' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Program not set')
            ->assertSee('Year level not set');
    }

    public function test_teach_and_learn_skills_store_their_own_proficiencies(): void
    {
        $user = User::factory()->create();
        $laravel = $this->createApprovedSkill('Laravel');
        $figma = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'teaching_skills' => [$laravel->id],
                'teaching_proficiencies' => [$laravel->id => 'advanced'],
                'learning_skills' => [$figma->id],
                'learning_proficiencies' => [$figma->id => 'beginner'],
            ])
            ->assertRedirect(route('onboarding.availability'));

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
            'proficiency' => 'advanced',
        ]);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $figma->id,
            'type' => 'learn',
            'proficiency' => 'beginner',
        ]);
    }

    #[DataProvider('validProficiencies')]
    public function test_each_supported_proficiency_is_accepted(string $proficiency): void
    {
        $user = User::factory()->create();
        $teachSkill = $this->createApprovedSkill('Teach '.$proficiency);
        $learnSkill = $this->createApprovedSkill('Learn '.$proficiency);

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'teaching_skills' => [$teachSkill->id],
                'teaching_proficiencies' => [$teachSkill->id => $proficiency],
                'learning_skills' => [$learnSkill->id],
                'learning_proficiencies' => [$learnSkill->id => $proficiency],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_skills', [
            'skill_id' => $teachSkill->id,
            'proficiency' => $proficiency,
        ]);
    }

    public function test_missing_and_invalid_proficiencies_are_rejected(): void
    {
        $user = User::factory()->create();
        $teachSkill = $this->createApprovedSkill('Laravel');
        $learnSkill = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'teaching_skills' => [$teachSkill->id],
                'teaching_proficiencies' => [$teachSkill->id => 'expert'],
                'learning_skills' => [$learnSkill->id],
                'learning_proficiencies' => [],
            ])
            ->assertSessionHasErrors([
                "teaching_proficiencies.{$teachSkill->id}",
                "learning_proficiencies.{$learnSkill->id}",
            ]);

        $this->assertDatabaseCount('user_skills', 0);
    }

    public function test_unapproved_skill_from_another_user_cannot_be_selected(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $unapprovedSkill = Skill::create([
            'name' => 'Private Suggestion',
            'created_by' => $otherUser->id,
            'is_approved' => false,
        ]);
        $learnSkill = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'teaching_skills' => [$unapprovedSkill->id],
                'teaching_proficiencies' => [$unapprovedSkill->id => 'advanced'],
                'learning_skills' => [$learnSkill->id],
                'learning_proficiencies' => [$learnSkill->id => 'beginner'],
            ])
            ->assertSessionHasErrors('teaching_skills.0');

        $this->assertDatabaseCount('user_skills', 0);
    }

    public function test_custom_skill_suggestion_reuses_case_insensitive_match_and_preserves_capitalization(): void
    {
        $user = User::factory()->create();
        $laravel = $this->createApprovedSkill('Laravel');
        $figma = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'new_teaching_skills' => '  laravel  , LARAVEL',
                'new_teaching_proficiency' => 'advanced',
                'learning_skills' => [$figma->id],
                'learning_proficiencies' => [$figma->id => 'beginner'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Skill::query()->whereRaw('LOWER(name) = ?', ['laravel'])->count());
        $this->assertSame('Laravel', $laravel->fresh()->name);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
            'proficiency' => 'advanced',
        ]);
    }

    public function test_new_custom_skill_remains_unapproved_and_owned_by_suggester(): void
    {
        $user = User::factory()->create();
        $learnSkill = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.store'), [
                'new_teaching_skills' => 'Campus Podcasting',
                'new_teaching_proficiency' => 'intermediate',
                'learning_skills' => [$learnSkill->id],
                'learning_proficiencies' => [$learnSkill->id => 'beginner'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skills', [
            'name' => 'Campus Podcasting',
            'is_approved' => false,
            'created_by' => $user->id,
            'skill_category_id' => null,
        ]);
    }

    public function test_historical_null_proficiency_remains_readable(): void
    {
        $user = User::factory()->onboarded()->create();
        $skill = $this->createApprovedSkill('Historical Skill');
        $user->teachingSkills()->attach($skill, ['type' => 'teach']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Historical Skill')
            ->assertSee('Proficiency not set');

        $this->assertNull($user->teachingSkills()->first()->pivot->proficiency);
    }

    public static function validYearLevels(): array
    {
        return ['first' => [1], 'second' => [2], 'third' => [3], 'fourth' => [4], 'fifth' => [5]];
    }

    public static function validProficiencies(): array
    {
        return [
            'beginner' => ['beginner'],
            'intermediate' => ['intermediate'],
            'advanced' => ['advanced'],
        ];
    }

    /** @return array<string, mixed> */
    private function profilePayload(Program $program, mixed $yearLevel): array
    {
        return [
            'school_organization' => 'FEU Institute of Technology',
            'program_id' => $program->id,
            'year_level' => $yearLevel,
            'bio' => 'College student ready to swap skills.',
        ];
    }

    private function createApprovedSkill(string $name): Skill
    {
        return Skill::create(['name' => $name, 'is_approved' => true]);
    }
}
