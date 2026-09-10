<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsSkillControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_open_the_skill_settings_page(): void
    {
        $this->get(route('settings.skills.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_skill_settings_page_shows_both_pickers_with_current_selections(): void
    {
        $user = User::factory()->onboarded()->create();
        $teaching = Skill::create(['name' => 'Laravel', 'is_approved' => true]);
        $learning = Skill::create(['name' => 'Figma', 'is_approved' => true]);

        $user->teachingSkills()->attach($teaching, ['type' => 'teach']);
        $user->learningSkills()->attach($learning, ['type' => 'learn']);

        $response = $this->actingAs($user)
            ->get(route('settings.skills.edit'))
            ->assertOk()
            ->assertSee('What can you teach?')
            ->assertSee('What do you want to learn?')
            ->assertSee('Laravel')
            ->assertSee('Figma');

        $response->assertSee('name="teaching_skills[]"', false);
        $response->assertSee('name="learning_skills[]"', false);
    }

    public function test_user_can_replace_their_teaching_skills(): void
    {
        $user = User::factory()->onboarded()->create();
        $oldSkill = Skill::create(['name' => 'Old Skill', 'is_approved' => true]);
        $newSkill = Skill::create(['name' => 'New Skill', 'is_approved' => true]);
        $learningSkill = Skill::create(['name' => 'Learning Skill', 'is_approved' => true]);

        $user->teachingSkills()->attach($oldSkill, ['type' => 'teach']);
        $user->learningSkills()->attach($learningSkill, ['type' => 'learn']);

        $this->actingAs($user)
            ->patch(route('settings.skills.teaching.update'), [
                'teaching_skills' => [$newSkill->id],
            ])
            ->assertRedirect(route('settings.skills.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame([$newSkill->id], $user->teachingSkills()->pluck('skills.id')->all());
        $this->assertSame([$learningSkill->id], $user->learningSkills()->pluck('skills.id')->all());
    }

    public function test_user_can_replace_their_learning_skills(): void
    {
        $user = User::factory()->onboarded()->create();
        $teachingSkill = Skill::create(['name' => 'Teaching Skill', 'is_approved' => true]);
        $oldSkill = Skill::create(['name' => 'Old Goal', 'is_approved' => true]);
        $newSkill = Skill::create(['name' => 'New Goal', 'is_approved' => true]);

        $user->teachingSkills()->attach($teachingSkill, ['type' => 'teach']);
        $user->learningSkills()->attach($oldSkill, ['type' => 'learn']);

        $this->actingAs($user)
            ->patch(route('settings.skills.learning.update'), [
                'learning_skills' => [$newSkill->id],
            ])
            ->assertRedirect(route('settings.skills.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame([$newSkill->id], $user->learningSkills()->pluck('skills.id')->all());
        $this->assertSame([$teachingSkill->id], $user->teachingSkills()->pluck('skills.id')->all());
    }

    public function test_each_section_requires_at_least_one_skill(): void
    {
        $user = User::factory()->onboarded()->create();
        $skill = Skill::create(['name' => 'Kept Skill', 'is_approved' => true]);
        $user->teachingSkills()->attach($skill, ['type' => 'teach']);

        $this->actingAs($user)
            ->patch(route('settings.skills.teaching.update'), [])
            ->assertSessionHasErrors('teaching_skills');

        $this->actingAs($user)
            ->patch(route('settings.skills.learning.update'), [])
            ->assertSessionHasErrors('learning_skills');

        $this->assertSame([$skill->id], $user->teachingSkills()->pluck('skills.id')->all());
    }

    public function test_suggested_skill_is_created_unapproved_and_owned_by_the_user(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->patch(route('settings.skills.teaching.update'), [
                'new_teaching_skills' => 'Advanced Origami',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skills', [
            'name' => 'Advanced Origami',
            'is_approved' => false,
            'created_by' => $user->id,
        ]);

        $this->assertSame(['Advanced Origami'], $user->teachingSkills()->pluck('skills.name')->all());
    }

    public function test_unapproved_skill_from_another_user_cannot_be_selected(): void
    {
        $user = User::factory()->onboarded()->create();
        $otherUser = User::factory()->onboarded()->create();
        $privateSkill = Skill::create([
            'name' => 'Private Skill',
            'is_approved' => false,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->patch(route('settings.skills.teaching.update'), [
                'teaching_skills' => [$privateSkill->id],
            ])
            ->assertSessionHasErrors('teaching_skills.0');

        $this->assertSame([], $user->teachingSkills()->pluck('skills.id')->all());
    }
}
