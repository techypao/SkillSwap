<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_navigation_uses_the_brand_mark_and_links_the_account_avatar_to_the_profile(): void
    {
        $user = User::factory()->onboarded()->create(['name' => 'Justine']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('src="'.asset('images/skillswap-mark.png').'"', false)
            ->assertSee('aria-label="SkillSwap dashboard"', false)
            ->assertSee('href="'.route('profile.show').'" data-profile-link', false)
            ->assertSee('aria-label="View your profile"', false);
    }
}
