<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProgramPickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_onboarding_page_shows_program_search_with_only_active_programs(): void
    {
        Program::create(['name' => 'Bachelor of Science in Information Technology', 'abbreviation' => 'BSIT']);
        Program::create(['name' => 'Retired Program', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('onboarding.profile'))
            ->assertOk()
            ->assertSee('Search or enter your program...')
            ->assertSee('name="program_id" value=""', false)
            ->assertSee('name="program_name"', false)
            ->assertSee('Choose a suggestion or keep your typed program name.')
            ->assertSee('Bachelor of Science in Information Technology')
            ->assertDontSee('Retired Program');
    }

    public function test_settings_page_shows_the_current_program_as_selected(): void
    {
        $program = Program::create(['name' => 'Bachelor of Science in Computer Science', 'abbreviation' => 'BSCS']);
        $user = User::factory()->onboarded()->create(['program_id' => $program->id]);

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('name="program_id" value="'.$program->id.'"', false)
            ->assertSee('Bachelor of Science in Computer Science');
    }

    public function test_program_names_are_escaped_in_the_embedded_options(): void
    {
        Program::create(['name' => '</script><script>alert(1)</script>']);

        $this->actingAs(User::factory()->create())
            ->get(route('onboarding.profile'))
            ->assertOk()
            ->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_custom_program_can_be_saved_when_it_is_not_in_the_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [
                'school_organization' => 'Small Provincial College',
                'program_id' => '',
                'program_name' => 'Diploma in Renewable Energy Technology',
                'year_level' => 2,
                'bio' => 'Hello there.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('onboarding.skills.teach'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'program_id' => null,
            'program_name' => 'Diploma in Renewable Energy Technology',
        ]);

        $this->actingAs($user)
            ->get(route('onboarding.skills.teach'))
            ->assertOk();
    }

    public function test_catalog_program_overrides_a_tampered_custom_name(): void
    {
        $user = User::factory()->create();
        $program = Program::create(['name' => 'Bachelor of Science in Computer Science']);

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [
                'school_organization' => 'Small Provincial College',
                'program_id' => $program->id,
                'program_name' => 'Something Else',
                'year_level' => 2,
                'bio' => 'Hello there.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'program_id' => $program->id,
            'program_name' => 'Bachelor of Science in Computer Science',
        ]);
    }

    public function test_program_or_custom_name_is_required_and_catalog_id_is_validated(): void
    {
        $user = User::factory()->create();
        $inactiveProgram = Program::create(['name' => 'Retired Program', 'is_active' => false]);
        $payload = [
            'school_organization' => 'Small Provincial College',
            'year_level' => 2,
            'bio' => 'Hello there.',
        ];

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$payload, 'program_id' => ''])
            ->assertSessionHasErrors([
                'program_name' => 'Please select your program or enter your program name.',
            ]);
        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$payload, 'program_id' => $inactiveProgram->id])
            ->assertSessionHasErrors([
                'program_id' => 'Please select a program from the list or enter your program name.',
            ]);

        $this->assertNull($user->fresh()->program_id);
        $this->assertNull($user->fresh()->program_name);
    }

    public function test_settings_can_replace_a_catalog_program_with_a_custom_program(): void
    {
        $program = Program::create(['name' => 'Bachelor of Science in Computer Science']);
        $user = User::factory()->onboarded()->create([
            'program_id' => $program->id,
            'program_name' => $program->name,
        ]);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'school_organization' => 'Small Provincial College',
                'program_id' => '',
                'program_name' => 'Associate in Digital Animation',
                'year_level' => 2,
                'bio' => 'Hello there.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'program_id' => null,
            'program_name' => 'Associate in Digital Animation',
        ]);
    }
}
