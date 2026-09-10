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
            ->assertRedirect(route('onboarding.skills.teach'));

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

    public function test_teach_and_learn_steps_store_their_own_skills(): void
    {
        $user = User::factory()->create();
        $laravel = $this->createApprovedSkill('Laravel');
        $figma = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [
                'teaching_skills' => [$laravel->id],
            ])
            ->assertRedirect(route('onboarding.skills.learn'));

        $this->actingAs($user)
            ->post(route('onboarding.skills.learn.store'), [
                'learning_skills' => [$figma->id],
            ])
            ->assertRedirect(route('onboarding.availability'));

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
        ]);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $figma->id,
            'type' => 'learn',
        ]);
    }

    public function test_each_step_requires_at_least_one_skill(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [])
            ->assertSessionHasErrors('teaching_skills');

        $this->actingAs($user)
            ->post(route('onboarding.skills.learn.store'), [])
            ->assertSessionHasErrors('learning_skills');

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

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [
                'teaching_skills' => [$unapprovedSkill->id],
            ])
            ->assertSessionHasErrors('teaching_skills.0');

        $this->assertDatabaseCount('user_skills', 0);
    }

    public function test_custom_skill_suggestion_reuses_case_insensitive_match_and_preserves_capitalization(): void
    {
        $user = User::factory()->create();
        $laravel = $this->createApprovedSkill('Laravel');

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [
                'new_teaching_skills' => '  laravel  , LARAVEL',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Skill::query()->whereRaw('LOWER(name) = ?', ['laravel'])->count());
        $this->assertSame('Laravel', $laravel->fresh()->name);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
        ]);
    }

    public function test_new_custom_skill_remains_unapproved_and_owned_by_suggester(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [
                'new_teaching_skills' => 'Campus Podcasting',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skills', [
            'name' => 'Campus Podcasting',
            'is_approved' => false,
            'created_by' => $user->id,
            'skill_category_id' => null,
        ]);
    }

    public function test_teach_and_learn_steps_render_as_separate_pages(): void
    {
        $user = $this->userWithCompletedProfile();
        $laravel = $this->createApprovedSkill('Laravel');

        $this->actingAs($user)
            ->get(route('onboarding.skills.teach'))
            ->assertOk()
            ->assertSee('What are your skills?')
            ->assertSee('Step 3 of 5')
            ->assertSee('What can you teach?')
            ->assertDontSee('What do you want to learn?')
            ->assertSee('teaching_skills[]', false)
            ->assertDontSee('learning_skills[]', false);

        $user->teachingSkills()->attach($laravel, ['type' => 'teach']);

        $this->actingAs($user)
            ->get(route('onboarding.skills.learn'))
            ->assertOk()
            ->assertSee('What do you want to learn?')
            ->assertSee('Step 4 of 5')
            ->assertSee('learning_skills[]', false)
            ->assertDontSee('teaching_skills[]', false)
            ->assertDontSee('What can you teach?');
    }

    public function test_learn_step_is_not_reachable_before_teaching_skills_are_saved(): void
    {
        $user = $this->userWithCompletedProfile();

        $this->actingAs($user)
            ->get(route('onboarding.skills.learn'))
            ->assertRedirect(route('onboarding.skills.teach'));
    }

    public function test_each_step_only_replaces_its_own_side_of_the_skills(): void
    {
        $user = User::factory()->create();
        $laravel = $this->createApprovedSkill('Laravel');
        $php = $this->createApprovedSkill('PHP');
        $figma = $this->createApprovedSkill('Figma');

        $this->actingAs($user)->post(route('onboarding.skills.teach.store'), [
            'teaching_skills' => [$laravel->id],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('onboarding.skills.learn.store'), [
            'learning_skills' => [$figma->id],
        ])->assertSessionHasNoErrors();

        // Revisiting the teach step must not clear the saved learning goals.
        $this->actingAs($user)->post(route('onboarding.skills.teach.store'), [
            'teaching_skills' => [$php->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $figma->id,
            'type' => 'learn',
        ]);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $php->id,
            'type' => 'teach',
        ]);
        $this->assertDatabaseMissing('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $laravel->id,
            'type' => 'teach',
        ]);
    }

    public function test_teach_step_rejects_learning_only_payload(): void
    {
        $user = User::factory()->create();
        $figma = $this->createApprovedSkill('Figma');

        $this->actingAs($user)
            ->post(route('onboarding.skills.teach.store'), [
                'learning_skills' => [$figma->id],
            ])
            ->assertSessionHasErrors('teaching_skills');

        $this->assertDatabaseCount('user_skills', 0);
    }

    public function test_skills_render_on_the_dashboard_without_any_proficiency(): void
    {
        $user = User::factory()->onboarded()->create();
        $skill = $this->createApprovedSkill('Historical Skill');
        $user->teachingSkills()->attach($skill, ['type' => 'teach']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Historical Skill')
            ->assertDontSee('Proficiency')
            ->assertDontSee('Beginner');
    }

    public function test_historical_proficiency_values_are_ignored_but_not_destroyed(): void
    {
        $user = User::factory()->onboarded()->create();
        $legacy = $this->createApprovedSkill('Legacy Skill');
        $user->teachingSkills()->attach($legacy, ['type' => 'teach', 'proficiency' => 'advanced']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Legacy Skill')
            ->assertDontSee('Advanced');

        // The column is retained, so pre-existing values survive untouched.
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $legacy->id,
            'proficiency' => 'advanced',
        ]);
    }

    public static function validYearLevels(): array
    {
        return ['first' => [1], 'second' => [2], 'third' => [3], 'fourth' => [4], 'fifth' => [5]];
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

    private function userWithCompletedProfile(): User
    {
        return User::factory()->create([
            'school_organization' => 'State University',
            'program_id' => Program::create(['name' => 'BS Information Technology', 'is_active' => true])->id,
            'year_level' => 4,
            'bio' => 'Hello there.',
        ]);
    }
}
