<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_creates_an_account_with_one_welcome_credit(): void
    {
        $this->freezeTime();

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'new@example.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 1,
            'reason' => 'welcome_bonus',
            'skill_session_id' => null,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_failed_welcome_ledger_write_rolls_back_account_creation(): void
    {
        DB::statement("CREATE TRIGGER reject_welcome_bonus BEFORE INSERT ON credit_transactions WHEN NEW.reason = 'welcome_bonus' BEGIN SELECT RAISE(ABORT, 'Ledger unavailable'); END");

        try {
            $this->post(route('register.store'), $this->registrationPayload())
                ->assertStatus(500);

            $this->assertGuest();
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('credit_transactions', 0);
        } finally {
            DB::statement('DROP TRIGGER reject_welcome_bonus');
        }
    }

    /** @return array{name: string, email: string, password: string, password_confirmation: string} */
    private function registrationPayload(): array
    {
        return [
            'name' => 'New Member',
            'email' => 'new@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }
}
