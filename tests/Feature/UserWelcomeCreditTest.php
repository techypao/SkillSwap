<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserWelcomeCreditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creating_a_user_directly_awards_the_welcome_credit_only_on_first_save(): void
    {
        $user = User::factory()->create();

        $this->assertSame(1, $user->skill_credits);
        $this->assertSame(1, $user->fresh()->skill_credits);

        $user->save();
        $user->update(['name' => 'Updated Name']);

        $this->assertSame(1, $user->fresh()->skill_credits);
        $transaction = $user->creditTransactions()->sole();
        $this->assertSame('welcome_bonus', $transaction->reason);
        $this->assertSame(1, $transaction->amount);
        $this->assertNull($transaction->skill_session_id);
        $this->assertSame($user->creditTransactions()->sum('amount'), $user->fresh()->skill_credits);
    }

    public function test_login_and_repeated_profile_views_do_not_repeat_the_welcome_credit(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->get(route('profile.show'))
            ->assertSee('Welcome to SkillSwap')
            ->assertSee('Total Earned: 1')
            ->assertSee('Total Spent: 0');
        $this->get(route('profile.show'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, $user->fresh()->skill_credits);
        $this->assertSame(1, $user->creditTransactions()->count());
    }

    #[DataProvider('existingBalances')]
    public function test_existing_users_keep_their_balance_without_receiving_a_welcome_bonus(int $balance): void
    {
        $user = User::factory()->onboarded()->createQuietly(['skill_credits' => $balance]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('profile.show'))->assertOk();
        $user->update(['bio' => 'Updated existing profile']);

        $this->assertSame($balance, $user->fresh()->skill_credits);
        $this->assertSame(0, $user->creditTransactions()->count());
    }

    public static function existingBalances(): array
    {
        return ['zero balance' => [0], 'positive balance' => [7]];
    }
}
