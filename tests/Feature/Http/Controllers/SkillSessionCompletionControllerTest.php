<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SkillSessionCompletionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sender_first_confirmation_at_exact_end_records_timestamp_without_completing_or_awarding_credits(): void
    {
        [$sender, $recipient, , $session] = $this->createConfirmedSession();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'You confirmed completion. Waiting for the other participant.');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_CONFIRMED,
            'sender_confirmed_at' => '2026-09-09 08:00:00',
            'recipient_confirmed_at' => null,
            'completed_at' => null,
        ]);
        $this->assertSame(1, $sender->fresh()->skill_credits);
        $this->assertSame(1, $recipient->fresh()->skill_credits);
        $this->assertSame(0, CreditTransaction::where('reason', CreditTransaction::REASON_SESSION_COMPLETED)->count());
    }

    public function test_recipient_confirmation_maps_to_recipient_timestamp(): void
    {
        [, $recipient, , $session] = $this->createConfirmedSession();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'sender_confirmed_at' => null,
            'recipient_confirmed_at' => '2026-09-09 08:00:00',
            'status' => SkillSession::STATUS_CONFIRMED,
        ]);
    }

    public function test_second_confirmation_completes_session_and_awards_each_participant_once(): void
    {
        [$sender, $recipient, , $session] = $this->createConfirmedSession([
            'sender_confirmed_at' => '2026-09-09 07:30:00',
        ]);
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'Skill swap completed! You each earned +1 Skill Credit.');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_COMPLETED,
            'completed_at' => '2026-09-09 08:00:00',
        ]);
        $this->assertSame(2, $sender->fresh()->skill_credits);
        $this->assertSame(2, $recipient->fresh()->skill_credits);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $sender->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_COMPLETED,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $recipient->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_COMPLETED,
        ]);
        $this->assertDatabaseCount('credit_transactions', 4);
    }

    public function test_repeated_completion_requests_do_not_award_additional_credits(): void
    {
        [$sender, $recipient, , $session] = $this->createConfirmedSession([
            'sender_confirmed_at' => '2026-09-09 07:30:00',
        ]);
        $this->travelTo('2026-09-09 08:00:00');
        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session));

        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('info', 'This skill swap is already completed.');
        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('info', 'This skill swap is already completed.');

        $this->assertSame(2, $sender->fresh()->skill_credits);
        $this->assertSame(2, $recipient->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 4);
    }

    public function test_future_confirmed_session_cannot_be_confirmed_complete(): void
    {
        [$sender, , , $session] = $this->createConfirmedSession([
            'scheduled_at' => '2026-09-09 09:00:00',
        ]);
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('info', 'This session can only be marked complete after it has ended.');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'sender_confirmed_at' => null,
            'status' => SkillSession::STATUS_CONFIRMED,
        ]);
        $this->actingAs($sender)->get(route('swap-requests.chat', $session->swapRequest))
            ->assertSee('Completion available after the session ends.')
            ->assertDontSee('Confirm Session Completed');
    }

    #[DataProvider('ongoingTimes')]
    public function test_ongoing_session_cannot_be_confirmed_or_offer_a_completion_action(string $currentTime): void
    {
        [$sender, $recipient, $swapRequest, $session] = $this->createConfirmedSession();
        $this->travelTo($currentTime);

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('info', 'This session can only be marked complete after it has ended.');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_CONFIRMED,
            'sender_confirmed_at' => null,
            'recipient_confirmed_at' => null,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame(1, $sender->fresh()->skill_credits);
        $this->assertSame(1, $recipient->fresh()->skill_credits);
        $this->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Completion available after the session ends.')
            ->assertDontSee('Confirm Session Completed')
            ->assertDontSee('AWAITING COMPLETION');
    }

    public function test_application_timezone_end_boundary_and_later_confirmation_preserve_the_existing_credit_flow(): void
    {
        [$sender, $recipient, , $session] = $this->createConfirmedSession([
            'scheduled_at' => '2026-09-10 00:00:00',
            'duration_minutes' => 30,
        ]);
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('2026-09-10T00:30:00+08:00', $session->endsAt()->toIso8601String());
        $this->travelTo(Carbon::parse('2026-09-09 16:29:59', 'UTC'));

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('info', 'This session can only be marked complete after it has ended.');
        $this->assertNull($session->fresh()->sender_confirmed_at);

        $this->travelTo(Carbon::parse('2026-09-09 16:30:00', 'UTC'));
        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'You confirmed completion. Waiting for the other participant.');
        $this->assertSame('2026-09-10 00:30:00', $session->fresh()->sender_confirmed_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('credit_transactions', 2);

        $this->travelTo(Carbon::parse('2026-09-09 16:31:00', 'UTC'));
        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'Skill swap completed! You each earned +1 Skill Credit.');
        $this->assertSame(SkillSession::STATUS_COMPLETED, $session->fresh()->status);
        $this->assertSame(2, $sender->fresh()->skill_credits);
        $this->assertSame(2, $recipient->fresh()->skill_credits);
        $this->assertSame(2, $session->creditTransactions()->where('reason', 'session_completed')->where('amount', 1)->count());
        $this->assertDatabaseCount('credit_transactions', 4);
    }

    #[DataProvider('invalidStoredSchedules')]
    public function test_invalid_stored_schedule_is_rejected_with_a_readable_chat_message(string $field, mixed $value): void
    {
        [$sender, , , $session] = $this->createConfirmedSession();
        $this->travelTo('2026-09-10 08:00:00');
        SkillSession::whereKey($session->id)->update([$field => $value]);

        $this->actingAs($sender)->followingRedirects()
            ->patch(route('skill-sessions.completion.store', $session))
            ->assertOk()
            ->assertSee('This session cannot be marked complete because its schedule is missing or invalid.')
            ->assertDontSee('Confirm Session Completed');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_CONFIRMED,
            'sender_confirmed_at' => null,
            'recipient_confirmed_at' => null,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[DataProvider('ineligibleSwapStatuses')]
    public function test_non_accepted_swaps_cannot_confirm_even_after_the_session_ends(string $status): void
    {
        [$sender, , $swapRequest, $session] = $this->createConfirmedSession();
        $swapRequest->update(['status' => $status]);
        $this->travelTo('2026-09-09 08:01:00');

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))->assertForbidden();

        $this->assertNull($session->fresh()->sender_confirmed_at);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_dashboard_excludes_ongoing_sessions_without_hiding_older_ended_sessions(): void
    {
        $this->travelTo('2026-09-09 08:00:00');
        [$sender, $recipient, , $endedSession] = $this->createConfirmedSession();
        for ($index = 0; $index < 6; $index++) {
            $this->createConfirmedSession(['scheduled_at' => '2026-09-09 07:30:00'], $sender, $recipient);
        }

        $response = $this->actingAs($sender)->get(route('dashboard'))->assertOk();

        $this->assertSame([$endedSession->id], $response->viewData('awaitingCompletionSessions')->pluck('id')->all());
    }

    public static function ongoingTimes(): array
    {
        return [
            'exact start' => ['2026-09-09 07:00:00'],
            'mid-session' => ['2026-09-09 07:30:00'],
            'one second before end' => ['2026-09-09 07:59:59'],
            'one microsecond before end' => ['2026-09-09 07:59:59.999999'],
        ];
    }

    public static function invalidStoredSchedules(): array
    {
        return [
            'unparseable start' => ['scheduled_at', 'invalid-date'],
            'impossible date' => ['scheduled_at', '2026-02-30 07:00:00'],
            'zero duration' => ['duration_minutes', 0],
            'negative duration' => ['duration_minutes', -30],
            'malformed duration' => ['duration_minutes', 'invalid'],
        ];
    }

    public static function ineligibleSwapStatuses(): array
    {
        return [
            'pending' => [SwapRequest::STATUS_PENDING],
            'rejected' => [SwapRequest::STATUS_REJECTED],
            'cancelled' => [SwapRequest::STATUS_CANCELLED],
        ];
    }

    public function test_proposed_and_cancelled_sessions_cannot_be_confirmed_complete(): void
    {
        foreach ([SkillSession::STATUS_PROPOSED, SkillSession::STATUS_CANCELLED] as $status) {
            [$sender, , , $session] = $this->createConfirmedSession(['status' => $status]);

            $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
                ->assertSessionHas('info', 'Only a confirmed session can be completed.');
        }

        $this->assertSame(0, CreditTransaction::where('reason', CreditTransaction::REASON_SESSION_COMPLETED)->count());
    }

    public function test_guest_unrelated_admin_and_incomplete_participant_cannot_confirm_completion(): void
    {
        [$sender, , , $session] = $this->createConfirmedSession();

        $this->patch(route('skill-sessions.completion.store', $session))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->onboarded()->create())
            ->patch(route('skill-sessions.completion.store', $session))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('skill-sessions.completion.store', $session))->assertRedirect(route('admin.dashboard'));

        $sender->update(['onboarding_completed' => false]);
        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertRedirect(route('onboarding.welcome'));
        $this->assertSame(0, CreditTransaction::where('reason', CreditTransaction::REASON_SESSION_COMPLETED)->count());
    }

    public function test_database_uniqueness_prevents_duplicate_session_reward(): void
    {
        [$sender, , , $session] = $this->createConfirmedSession();
        CreditTransaction::create([
            'user_id' => $sender->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_COMPLETED,
        ]);

        $this->expectException(QueryException::class);

        CreditTransaction::create([
            'user_id' => $sender->id,
            'skill_session_id' => $session->id,
            'amount' => 1,
            'reason' => CreditTransaction::REASON_SESSION_COMPLETED,
        ]);
    }

    public function test_two_independent_completed_sessions_award_credits_independently(): void
    {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        [, , , $firstSession] = $this->createConfirmedSession([], $sender, $recipient);
        [, , , $secondSession] = $this->createConfirmedSession([], $sender, $recipient);
        $this->travelTo('2026-09-09 08:00:00');

        foreach ([$firstSession, $secondSession] as $session) {
            $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session));
            $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session));
        }

        $this->assertSame(3, $sender->fresh()->skill_credits);
        $this->assertSame(3, $recipient->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 6);
    }

    public function test_chat_remains_active_and_accepts_messages_after_first_confirmation(): void
    {
        [$sender, $recipient, $swapRequest, $session] = $this->createConfirmedSession();
        $this->travelTo('2026-09-09 08:00:00');
        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session));

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('You confirmed completion')->assertSee('Send Message');
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Jiro has confirmed completion')->assertSee('Confirm Session Completed');
        $this->actingAs($recipient)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => 'I will confirm now.',
        ])->assertRedirect(route('swap-requests.chat', $swapRequest));
        $this->assertDatabaseHas('swap_messages', ['message' => 'I will confirm now.']);
    }

    public function test_completed_chat_is_read_only_but_preserves_history_for_participants(): void
    {
        [$sender, $recipient, $swapRequest, $session] = $this->createConfirmedSession([
            'sender_confirmed_at' => '2026-09-09 07:30:00',
        ]);
        SwapMessage::create([
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $sender->id,
            'message' => 'Historical message',
        ]);
        $this->travelTo('2026-09-09 08:00:00');
        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session));

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('SKILL SWAP COMPLETED')->assertSee('Historical message')
            ->assertSee('This conversation is closed.')->assertDontSee('Send Message')
            ->assertDontSee('Propose Session')->assertDontSee('Confirm Session Completed');
        $this->actingAs($sender)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => 'Must not be stored',
        ])->assertSessionHas('info', 'This conversation is closed.');
        $this->assertDatabaseMissing('swap_messages', ['message' => 'Must not be stored']);
    }

    public function test_completed_exchange_cannot_create_another_proposal(): void
    {
        [$sender, , $swapRequest] = $this->createConfirmedSession([
            'status' => SkillSession::STATUS_COMPLETED,
            'sender_confirmed_at' => now(),
            'recipient_confirmed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($sender)->get(route('skill-sessions.create', $swapRequest))
            ->assertSessionHas('info', 'This swap already has an active session proposal.');
        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertSessionHas('info', 'This swap already has an active session proposal.');
        $this->assertDatabaseCount('skill_sessions', 1);
    }

    public function test_unrelated_user_cannot_view_completed_conversation(): void
    {
        [, , $swapRequest] = $this->createConfirmedSession([
            'status' => SkillSession::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->actingAs(User::factory()->onboarded()->create())
            ->get(route('swap-requests.chat', $swapRequest))->assertForbidden();
    }

    public function test_completed_swap_moves_to_completed_dashboard_with_correct_perspectives_and_credits(): void
    {
        [$sender, $recipient, $swapRequest, $session] = $this->createConfirmedSession([
            'sender_confirmed_at' => '2026-09-09 07:30:00',
        ]);
        $this->travelTo('2026-09-09 08:00:00');
        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session));

        $senderDashboard = $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('Completed Swaps')->assertSee('With Justine')
            ->assertSee('You taught: TypeScript')->assertSee('You learned: PHP')
            ->assertSee('+1 Skill Credit earned')->assertSee('View Conversation')
            ->assertDontSee('No completed swaps yet.');
        $this->assertSame(2, $senderDashboard->viewData('user')->skill_credits);
        $this->actingAs($recipient)->get(route('dashboard'))
            ->assertSee('With Jiro')->assertSee('You taught: PHP')->assertSee('You learned: TypeScript');
        $this->assertFalse($this->dashboardCollectionContains($sender, 'acceptedSwapRequests', $swapRequest->id));
        $this->assertFalse($this->dashboardCollectionContains($sender, 'upcomingSessions', $session->id));
    }

    public function test_future_confirmed_is_upcoming_and_past_confirmed_is_awaiting_completion(): void
    {
        [$sender, , , $pastSession] = $this->createConfirmedSession();
        [, , , $futureSession] = $this->createConfirmedSession([
            'scheduled_at' => '2026-09-09 09:00:00',
        ], $sender);
        $this->travelTo('2026-09-09 08:00:00');

        $response = $this->actingAs($sender)->get(route('dashboard'));

        $this->assertTrue($response->viewData('upcomingSessions')->contains($futureSession));
        $this->assertFalse($response->viewData('upcomingSessions')->contains($pastSession));
        $this->assertTrue($response->viewData('awaitingCompletionSessions')->contains($pastSession));
        $response->assertSee('Awaiting Completion');
    }

    /** @return array{User, User, SwapRequest, SkillSession} */
    private function createConfirmedSession(
        array $sessionAttributes = [],
        ?User $sender = null,
        ?User $recipient = null
    ): array {
        $sender ??= User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient ??= User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::firstOrCreate(['name' => 'TypeScript'], ['is_approved' => true]);
        $requestedSkill = Skill::firstOrCreate(['name' => 'PHP'], ['is_approved' => true]);
        $swapRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
        $session = SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'scheduled_at' => '2026-09-09 07:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => '2026-09-08 08:00:00',
            ...$sessionAttributes,
        ]);

        return [$sender, $recipient, $swapRequest, $session];
    }

    private function dashboardCollectionContains(User $user, string $key, int $modelId): bool
    {
        return $this->actingAs($user)->get(route('dashboard'))->viewData($key)->contains('id', $modelId);
    }

    /** @return array{teaching_side: string, date: string, time: string, duration_minutes: int, meeting_type: string} */
    private function proposalPayload(): array
    {
        return [
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '15:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
        ];
    }
}
