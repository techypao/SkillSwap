<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Program;
use App\Models\Review;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_open_the_profile_page(): void
    {
        $this->get(route('profile.show'))
            ->assertRedirect(route('login'));
    }

    public function test_user_who_has_not_finished_onboarding_is_sent_back_to_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertRedirect(route('onboarding.welcome'));
    }

    public function test_profile_shows_bio_and_academic_information(): void
    {
        $program = Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
            'is_active' => true,
        ]);

        $user = User::factory()->onboarded()->create([
            'school_organization' => 'FEU Institute of Technology',
            'program_id' => $program->id,
            'year_level' => 4,
            'bio' => 'I love teaching Laravel to first year students.',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('I love teaching Laravel to first year students.')
            ->assertSee('FEU Institute of Technology')
            ->assertSee($program->name)
            ->assertSee('Year 4');
    }

    public function test_profile_shows_teaching_and_learning_skills(): void
    {
        $user = User::factory()->onboarded()->create();
        $teaching = Skill::create(['name' => 'Laravel', 'is_approved' => true]);
        $learning = Skill::create(['name' => 'Figma', 'is_approved' => true]);

        $user->teachingSkills()->attach($teaching, ['type' => 'teach']);
        $user->learningSkills()->attach($learning, ['type' => 'learn']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Skills I Can Teach')
            ->assertSee('Laravel')
            ->assertSee('Skills I Want To Learn')
            ->assertSee('Figma');
    }

    public function test_profile_shows_current_balance_earned_spent_and_friendly_transaction_labels(): void
    {
        $user = User::factory()->onboarded()->createQuietly(['skill_credits' => 3]);
        $session = $this->completedSession($user);
        $skillName = $session->taughtSkill->name;

        CreditTransaction::create([
            'user_id' => $user->id,
            'skill_session_id' => null,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_WELCOME_BONUS,
        ]);
        CreditTransaction::create([
            'user_id' => $user->id,
            'skill_session_id' => $session->id,
            'amount' => 4,
            'reason' => CreditTransaction::REASON_SESSION_TAUGHT,
        ]);
        CreditTransaction::create([
            'user_id' => $user->id,
            'skill_session_id' => $session->id,
            'amount' => -2,
            'reason' => CreditTransaction::REASON_SESSION_LEARNED,
        ]);

        $response = $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Skill Credits')
            ->assertSee('Current Balance')
            ->assertSee('Total Earned: 5')
            ->assertSee('Total Spent: 2')
            ->assertSee('Welcome to SkillSwap')
            ->assertSee('Taught '.$skillName)
            ->assertSee('Learned '.$skillName)
            ->assertDontSee('welcome_bonus')
            ->assertDontSee('session_taught')
            ->assertDontSee('session_learned');

        $this->assertSame(3, $response->viewData('user')->skill_credits);
        $this->assertSame(5, $response->viewData('creditsEarned'));
        $this->assertSame(2, $response->viewData('creditsSpent'));
    }

    public function test_profile_limits_credit_history_to_latest_ten_in_newest_first_order(): void
    {
        $user = User::factory()->onboarded()->createQuietly();
        $transactionIds = [];

        for ($index = 1; $index <= 12; $index++) {
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'skill_session_id' => null,
                'amount' => 1,
                'reason' => 'test_event_'.$index,
            ]);
            DB::table('credit_transactions')->where('id', $transaction->id)->update([
                'created_at' => sprintf('2026-09-%02d 08:00:00', $index),
                'updated_at' => sprintf('2026-09-%02d 08:00:00', $index),
            ]);
            $transactionIds[] = $transaction->id;
        }

        $response = $this->actingAs($user)->get(route('profile.show'))->assertOk();

        $expectedIds = array_reverse(array_slice($transactionIds, 2));
        $this->assertSame($expectedIds, $response->viewData('user')->creditTransactions->pluck('id')->all());
        $this->assertCount(10, $response->viewData('user')->creditTransactions);
    }

    public function test_session_deletion_preserves_both_participants_ledger_and_balances_with_safe_labels(): void
    {
        $teacher = User::factory()->onboarded()->create();
        $learner = User::factory()->onboarded()->create();
        $session = $this->completedSession($learner, $teacher);
        $teacherTransaction = CreditTransaction::create([
            'user_id' => $teacher->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_TAUGHT,
        ]);
        $learnerTransaction = CreditTransaction::create([
            'user_id' => $learner->id,
            'skill_session_id' => $session->id,
            'amount' => -1,
            'reason' => CreditTransaction::REASON_SESSION_LEARNED,
        ]);
        $teacher->increment('skill_credits');
        $learner->decrement('skill_credits');

        $session->delete();

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $teacherTransaction->id,
            'skill_session_id' => null,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'id' => $learnerTransaction->id,
            'skill_session_id' => null,
        ]);
        $this->assertSame(2, $teacher->fresh()->skill_credits);
        $this->assertSame(0, $learner->fresh()->skill_credits);
        $this->assertBalanceMatchesLedger($teacher, $learner);
        $this->assertNull($teacherTransaction->fresh()->skillSession);
        $this->assertNull($learnerTransaction->fresh()->skillSession);
        $this->actingAs($teacher)->get(route('profile.show'))
            ->assertSee('Total Earned: 2')
            ->assertSee('Total Spent: 0')
            ->assertSee('Skill teaching session')
            ->assertDontSee('session_taught');
        $this->actingAs($learner)->get(route('profile.show'))
            ->assertSee('Total Earned: 1')
            ->assertSee('Total Spent: 1')
            ->assertSee('Skill learning session')
            ->assertDontSee('session_learned');
    }

    public function test_deleting_one_participant_preserves_the_surviving_participants_ledger(): void
    {
        $teacher = User::factory()->onboarded()->create();
        $learner = User::factory()->onboarded()->create();
        $session = $this->completedSession($learner, $teacher);
        $teacherTransaction = CreditTransaction::create([
            'user_id' => $teacher->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_TAUGHT,
        ]);
        $learnerTransaction = CreditTransaction::create([
            'user_id' => $learner->id,
            'skill_session_id' => $session->id,
            'amount' => -1,
            'reason' => CreditTransaction::REASON_SESSION_LEARNED,
        ]);
        $learner->decrement('skill_credits');

        $teacher->delete();

        $this->assertDatabaseMissing('credit_transactions', ['id' => $teacherTransaction->id]);
        $this->assertDatabaseHas('credit_transactions', [
            'id' => $learnerTransaction->id,
            'user_id' => $learner->id,
            'skill_session_id' => null,
            'amount' => -1,
            'reason' => CreditTransaction::REASON_SESSION_LEARNED,
        ]);
        $this->assertSame(0, $learner->fresh()->skill_credits);
        $this->assertBalanceMatchesLedger($learner);
        $this->actingAs($learner)->get(route('profile.show'))
            ->assertSee('Skill learning session')
            ->assertDontSee('session_learned');
        $this->actingAs($learner)->get(route('dashboard'))->assertOk();
    }

    public function test_profile_shows_reviews_written_about_the_user(): void
    {
        $user = User::factory()->onboarded()->create();
        $reviewer = User::factory()->onboarded()->create(['name' => 'Maria Santos']);
        $session = $this->completedSession($user, $reviewer);

        Review::create([
            'skill_session_id' => $session->id,
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $user->id,
            'rating' => 5,
            'comment' => 'Explained everything very clearly.',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Reviews About Me')
            ->assertSee('Maria Santos')
            ->assertSee('Explained everything very clearly.')
            ->assertSee('5.0');
    }

    public function test_profile_averages_multiple_review_ratings(): void
    {
        $user = User::factory()->onboarded()->create();

        foreach ([5, 4] as $rating) {
            $reviewer = User::factory()->onboarded()->create();
            $session = $this->completedSession($user, $reviewer);

            Review::create([
                'skill_session_id' => $session->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $user->id,
                'rating' => $rating,
                'comment' => 'Rating '.$rating,
            ]);
        }

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('2 reviews');
    }

    public function test_profile_does_not_show_reviews_or_credits_of_other_users(): void
    {
        $user = User::factory()->onboarded()->create();
        $otherUser = User::factory()->onboarded()->create();
        $reviewer = User::factory()->onboarded()->create(['name' => 'Someone Else']);
        $session = $this->completedSession($otherUser, $reviewer);

        Review::create([
            'skill_session_id' => $session->id,
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $otherUser->id,
            'rating' => 1,
            'comment' => 'A review for somebody else entirely.',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('No one has reviewed you yet.')
            ->assertDontSee('A review for somebody else entirely.');
    }

    public function test_empty_profile_renders_helpful_placeholders(): void
    {
        $user = User::factory()->onboarded()->createQuietly(['bio' => null]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('You have not written a bio yet.')
            ->assertSee('You have not earned any skill credits yet.')
            ->assertSee('No reviews yet. Complete a session to get your first review.');
    }

    private function completedSession(User $user, ?User $partner = null): SkillSession
    {
        $partner ??= User::factory()->onboarded()->create();

        $offeredSkill = Skill::create(['name' => 'Offered '.uniqid(), 'is_approved' => true]);
        $requestedSkill = Skill::create(['name' => 'Requested '.uniqid(), 'is_approved' => true]);

        $swapRequest = SwapRequest::create([
            'sender_id' => $partner->id,
            'recipient_id' => $user->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $partner->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.com/session',
            'status' => SkillSession::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    private function assertBalanceMatchesLedger(User ...$users): void
    {
        foreach ($users as $user) {
            $this->assertSame(
                $user->creditTransactions()->sum('amount'),
                $user->fresh()->skill_credits,
                'Stored Skill Credit balance must equal the ledger sum for user '.$user->id.'.'
            );
        }
    }
}
