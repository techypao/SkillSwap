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
            ->assertSee('Search your program...')
            ->assertSee('name="program_id" value=""', false)
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

    public function test_program_is_still_required_and_validated_server_side(): void
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
            ->assertSessionHasErrors('program_id');
        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$payload, 'program_id' => $inactiveProgram->id])
            ->assertSessionHasErrors('program_id');

        $this->assertNull($user->fresh()->program_id);
    }
}
