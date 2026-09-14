<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SchoolSelectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_onboarding_page_allows_a_school_outside_the_suggestion_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('onboarding.profile'))
            ->assertOk()
            ->assertSee('School / University')
            ->assertSee('Search or enter your school...')
            ->assertSee('name="school_organization"', false)
            ->assertSee('Choose a suggestion or keep your typed school name.')
            ->assertSee(route('schools.search'));
    }

    public function test_valid_canonical_school_can_be_saved_during_onboarding(): void
    {
        $user = User::factory()->create();
        $school = $this->feuTech();

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [
                ...$this->profilePayload(),
                'school_id' => $school->id,
                // A tampered free-text value must not override the canonical name.
                'school_organization' => 'Something Else',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('onboarding.skills.teach'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'school_id' => $school->id,
            'school_organization' => 'FEU Institute of Technology',
        ]);
    }

    public function test_invalid_school_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$this->profilePayload(), 'school_id' => 999999])
            ->assertSessionHasErrors('school_id');

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$this->profilePayload(), 'school_id' => 'feu'])
            ->assertSessionHasErrors('school_id');

        $this->assertNull($user->fresh()->school_id);
        $this->assertNull($user->fresh()->school_organization);
    }

    public function test_inactive_school_is_rejected(): void
    {
        $user = User::factory()->create();
        $inactiveSchool = School::create(['name' => 'Closed College', 'is_active' => false]);

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$this->profilePayload(), 'school_id' => $inactiveSchool->id])
            ->assertSessionHasErrors('school_id');

        $this->assertNull($user->fresh()->school_id);
    }

    public function test_manual_school_fallback_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [
                ...$this->profilePayload(),
                'school_id' => '',
                'school_organization' => 'Small Provincial College',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('onboarding.skills.teach'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'school_id' => null,
            'school_organization' => 'Small Provincial College',
        ]);
    }

    public function test_school_or_manual_entry_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.profile.store'), [...$this->profilePayload(), 'school_id' => ''])
            ->assertSessionHasErrors([
                'school_organization' => 'Please select your school or enter your school name.',
            ]);
    }

    public function test_existing_user_with_only_school_organization_still_works(): void
    {
        $legacyUser = User::factory()->onboarded()->create([
            'school_id' => null,
            'school_organization' => 'FEU Tech',
        ]);
        $viewer = User::factory()->onboarded()->create();

        $this->actingAs($legacyUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('FEU Tech');
        $this->actingAs($legacyUser)
            ->get(route('onboarding.profile'))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($legacyUser)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('FEU Tech');
        $this->actingAs($legacyUser)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('value="FEU Tech"', false);
        $this->actingAs($viewer)
            ->get(route('discover.index'))
            ->assertOk()
            ->assertSee('FEU Tech');
        $this->actingAs($viewer)
            ->get(route('matches.show', $legacyUser))
            ->assertOk()
            ->assertSee('FEU Tech');
    }

    public function test_existing_user_can_select_a_canonical_school_later_in_settings(): void
    {
        $legacyUser = User::factory()->onboarded()->create(['school_organization' => 'FEU Tech']);
        $school = $this->feuTech();

        $this->actingAs($legacyUser)
            ->patch(route('settings.profile.update'), [
                ...$this->settingsPayload($legacyUser),
                'school_id' => $school->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $legacyUser->id,
            'school_id' => $school->id,
            'school_organization' => 'FEU Institute of Technology',
        ]);

        $this->actingAs($legacyUser)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('FEU Institute of Technology')
            ->assertSee('value="'.$school->id.'"', false);
    }

    public function test_switching_to_manual_entry_in_settings_clears_the_canonical_school(): void
    {
        $school = $this->feuTech();
        $user = User::factory()->onboarded()->create([
            'school_id' => $school->id,
            'school_organization' => $school->name,
        ]);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), [
                ...$this->settingsPayload($user),
                'school_id' => '',
                'school_organization' => 'Transferred Community College',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'school_id' => null,
            'school_organization' => 'Transferred Community College',
        ]);
    }

    public function test_settings_rejects_inactive_school(): void
    {
        $user = User::factory()->onboarded()->create(['school_organization' => 'FEU Tech']);
        $inactiveSchool = School::create(['name' => 'Closed College', 'is_active' => false]);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), [
                ...$this->settingsPayload($user),
                'school_id' => $inactiveSchool->id,
            ])
            ->assertSessionHasErrors('school_id');

        $this->assertNull($user->fresh()->school_id);
    }

    public function test_canonical_school_displays_on_dashboard(): void
    {
        $user = $this->userWithCanonicalSchool();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('FEU Institute of Technology')
            ->assertDontSee('Old Free Text School');
    }

    public function test_canonical_school_displays_on_discover(): void
    {
        $otherUser = $this->userWithCanonicalSchool();

        $this->actingAs(User::factory()->onboarded()->create())
            ->get(route('discover.index'))
            ->assertOk()
            ->assertSee($otherUser->name)
            ->assertSee('FEU Institute of Technology')
            ->assertDontSee('Old Free Text School');
    }

    public function test_canonical_school_displays_on_compatibility_page(): void
    {
        $otherUser = $this->userWithCanonicalSchool();

        $this->actingAs(User::factory()->onboarded()->create())
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('FEU Institute of Technology')
            ->assertDontSee('Old Free Text School');
    }

    private function feuTech(): School
    {
        return School::create([
            'name' => 'FEU Institute of Technology',
            'abbreviation' => 'FEU Tech',
            'city' => 'Manila',
            'province' => 'Metro Manila',
        ]);
    }

    /**
     * A canonical school wins over a stale free-text value.
     */
    private function userWithCanonicalSchool(): User
    {
        return User::factory()->onboarded()->create([
            'school_id' => $this->feuTech()->id,
            'school_organization' => 'Old Free Text School',
        ]);
    }

    /** @return array<string, mixed> */
    private function profilePayload(): array
    {
        return [
            'program_id' => Program::firstOrCreate(['name' => 'BS Information Technology'], ['is_active' => true])->id,
            'year_level' => 3,
            'bio' => 'College student ready to swap skills.',
        ];
    }

    /** @return array<string, mixed> */
    private function settingsPayload(User $user): array
    {
        return [
            ...$this->profilePayload(),
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
