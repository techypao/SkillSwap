<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_open_the_settings_page(): void
    {
        $this->get(route('settings.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_user_who_has_not_finished_onboarding_is_sent_back_to_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertRedirect(route('onboarding.welcome'));
    }

    public function test_settings_page_shows_the_current_profile_details(): void
    {
        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('FEU Institute of Technology')
            ->assertSee($program->name);
    }

    public function test_user_can_update_their_profile_information(): void
    {
        $program = $this->activeProgram();
        $newProgram = Program::create(['name' => 'Bachelor of Science in Computer Science', 'is_active' => true]);
        $user = $this->onboardedUser($program);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), [
                'name' => 'Justine Claro',
                'email' => 'justine@example.com',
                'school_organization' => 'FEU Tech',
                'program_id' => $newProgram->id,
                'year_level' => 3,
                'bio' => 'Updated bio for the settings page.',
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Justine Claro',
            'email' => 'justine@example.com',
            'school_organization' => 'FEU Tech',
            'program_id' => $newProgram->id,
            'year_level' => 3,
            'bio' => 'Updated bio for the settings page.',
        ]);
    }

    public function test_email_must_be_unique_across_other_users(): void
    {
        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, ['email' => $otherUser->email]))
            ->assertSessionHasErrors('email');

        $this->assertSame('settings@example.com', $user->fresh()->email);
    }

    public function test_user_can_keep_their_own_email_address(): void
    {
        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, ['email' => $user->email]))
            ->assertSessionHasNoErrors();
    }

    public function test_inactive_program_and_invalid_year_level_are_rejected(): void
    {
        $program = $this->activeProgram();
        $inactiveProgram = Program::create(['name' => 'Retired Program', 'is_active' => false]);
        $user = $this->onboardedUser($program);

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, ['program_id' => $inactiveProgram->id]))
            ->assertSessionHasErrors('program_id');

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, ['year_level' => 9]))
            ->assertSessionHasErrors('year_level');

        $this->assertSame($program->id, $user->fresh()->program_id);
    }

    public function test_uploading_a_new_picture_replaces_the_old_one(): void
    {
        Storage::fake('public');

        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);
        $user->update(['profile_picture' => 'profile-pictures/old.jpg']);
        Storage::disk('public')->put('profile-pictures/old.jpg', 'old');

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, [
                'profile_picture' => UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg'),
            ]))
            ->assertSessionHasNoErrors();

        $newPath = $user->fresh()->profile_picture;

        $this->assertNotSame('profile-pictures/old.jpg', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('profile-pictures/old.jpg');
    }

    public function test_profile_picture_is_kept_when_no_new_file_is_uploaded(): void
    {
        Storage::fake('public');

        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);
        $user->update(['profile_picture' => 'profile-pictures/keep.jpg']);
        Storage::disk('public')->put('profile-pictures/keep.jpg', 'keep');

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program))
            ->assertSessionHasNoErrors();

        $this->assertSame('profile-pictures/keep.jpg', $user->fresh()->profile_picture);
        Storage::disk('public')->assertExists('profile-pictures/keep.jpg');
    }

    public function test_user_can_remove_their_profile_picture(): void
    {
        Storage::fake('public');

        $program = $this->activeProgram();
        $user = $this->onboardedUser($program);
        $user->update(['profile_picture' => 'profile-pictures/remove.jpg']);
        Storage::disk('public')->put('profile-pictures/remove.jpg', 'remove');

        $this->actingAs($user)
            ->patch(route('settings.profile.update'), $this->profilePayload($program, [
                'remove_profile_picture' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->profile_picture);
        Storage::disk('public')->assertMissing('profile-pictures/remove.jpg');
    }

    private function activeProgram(): Program
    {
        return Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
            'is_active' => true,
        ]);
    }

    private function onboardedUser(Program $program): User
    {
        return User::factory()->onboarded()->create([
            'email' => 'settings@example.com',
            'school_organization' => 'FEU Institute of Technology',
            'program_id' => $program->id,
            'year_level' => 4,
            'bio' => 'The original bio.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(Program $program, array $overrides = []): array
    {
        return [
            'name' => 'Settings User',
            'email' => 'settings@example.com',
            'school_organization' => 'FEU Institute of Technology',
            'program_id' => $program->id,
            'year_level' => 4,
            'bio' => 'A perfectly valid bio.',
            ...$overrides,
        ];
    }
}
