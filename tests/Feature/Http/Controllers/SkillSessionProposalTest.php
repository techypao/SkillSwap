<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SkillSessionProposalTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('sessionDirections')]
    public function test_zero_credit_learners_cannot_receive_a_proposal_from_either_participant(string $teachingSide, bool $senderProposes): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $teacher = $teachingSide === 'sender' ? $sender : $recipient;
        $learner = $teachingSide === 'sender' ? $recipient : $sender;
        $teacher->update(['skill_credits' => 5]);
        $learner->update(['skill_credits' => 0]);
        $proposer = $senderProposes ? $sender : $recipient;

        $this->actingAs($proposer)->from(route('swap-requests.chat', $swapRequest))
            ->followingRedirects()
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->proposalPayload(),
                'teaching_side' => $teachingSide,
            ])
            ->assertSee('The learner needs at least 1 Skill Credit before this session can be proposed.')
            ->assertSee('aria-expanded="true"', false);

        $this->assertDatabaseCount('skill_sessions', 0);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame(5, $teacher->fresh()->skill_credits);
        $this->assertSame(0, $learner->fresh()->skill_credits);
    }

    #[DataProvider('eligibleLearners')]
    public function test_eligible_learners_can_be_scheduled_and_confirmed_without_spending_credits(string $teachingSide, bool $senderProposes, int $learnerCredits): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $teacher = $teachingSide === 'sender' ? $sender : $recipient;
        $learner = $teachingSide === 'sender' ? $recipient : $sender;
        $teacher->update(['skill_credits' => 0]);
        $learner->update(['skill_credits' => $learnerCredits]);
        $proposer = $senderProposes ? $sender : $recipient;
        $responder = $senderProposes ? $recipient : $sender;

        $this->actingAs($proposer)->post(route('skill-sessions.store', $swapRequest), [
            ...$this->proposalPayload(),
            'teaching_side' => $teachingSide,
        ])->assertSessionHas('success', 'Session proposal sent!');

        $session = $swapRequest->skillSession;
        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_PROPOSED,
            'scheduled_at' => '2026-09-12 15:00:00',
            'teaching_side' => $teachingSide,
        ]);
        $this->assertSame(0, $teacher->fresh()->skill_credits);
        $this->assertSame($learnerCredits, $learner->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 2);

        $this->actingAs($responder)->patch(route('skill-sessions.agree', $session))
            ->assertSessionHas('success', 'Session confirmed!');

        $this->assertSame(SkillSession::STATUS_CONFIRMED, $session->fresh()->status);
        $this->assertSame(0, $teacher->fresh()->skill_credits);
        $this->assertSame($learnerCredits, $learner->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    public function test_direct_creation_checks_the_stored_balance_even_when_the_learner_is_cached(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = new SkillSession([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
        ]);
        $this->assertSame(1, $session->learner->skill_credits);
        $recipient->update(['skill_credits' => 0]);

        try {
            $session->save();
            $this->fail('A learner without credits must not receive a new session.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'teaching_side' => ['The learner needs at least 1 Skill Credit before this session can be proposed.'],
            ], $exception->errors());
        }

        $this->assertDatabaseCount('skill_sessions', 0);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    #[DataProvider('cancelledProposalDirections')]
    public function test_reproposing_cancelled_sessions_checks_the_new_learner_without_changing_the_old_session(string $teachingSide, bool $historical): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $session = $this->createSession($swapRequest, $sender, ['status' => SkillSession::STATUS_CANCELLED]);
        if ($historical) {
            SkillSession::whereKey($session->id)->update(['teaching_side' => null]);
        }
        $originalSession = $session->fresh()->getAttributes();
        $learner = $teachingSide === 'sender' ? $recipient : $sender;
        $learner->update(['skill_credits' => 0]);

        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), [
            ...$this->proposalPayload(),
            'teaching_side' => $teachingSide,
            'time' => '16:00',
        ])->assertSessionHasErrors([
            'teaching_side' => 'The learner needs at least 1 Skill Credit before this session can be proposed.',
        ]);

        $this->assertSame($originalSession, $session->fresh()->getAttributes());
        $this->assertDatabaseCount('skill_sessions', 1);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_one_credit_can_support_multiple_outstanding_proposals_without_reservations(): void
    {
        [$sender, $recipient, $firstSwap] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $sender->update(['skill_credits' => 0]);
        $secondSwap = $firstSwap->replicate();
        $secondSwap->save();
        $thirdSwap = $firstSwap->replicate();
        $thirdSwap->save();

        foreach ([$firstSwap, $secondSwap, $thirdSwap] as $swapRequest) {
            $this->actingAs($recipient)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
                ->assertSessionHas('success', 'Session proposal sent!');
        }

        $this->assertSame(3, SkillSession::where('status', SkillSession::STATUS_PROPOSED)->count());
        $this->assertSame(0, $sender->fresh()->skill_credits);
        $this->assertSame(1, $recipient->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    public function test_changing_a_proposal_direction_cannot_switch_to_a_learner_without_credit(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $sender->update(['skill_credits' => 0]);
        $session = $this->createSession($swapRequest, $sender);

        try {
            $session->update(['teaching_side' => SkillSession::TEACHING_SIDE_RECIPIENT]);
            $this->fail('Changing the learner must check the new learner balance.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('teaching_side', $exception->errors());
        }

        $this->assertSame(SkillSession::TEACHING_SIDE_SENDER, $session->fresh()->teaching_side);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    public function test_changing_the_swap_checks_its_learner_instead_of_the_cached_swap(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $sender->update(['skill_credits' => 0]);
        $session = $this->createSession($swapRequest, $sender);
        $this->assertTrue($session->learner->is($recipient));
        $otherSwap = $swapRequest->replicate();
        $otherSwap->fill([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'offered_skill_id' => $swapRequest->requested_skill_id,
            'requested_skill_id' => $swapRequest->offered_skill_id,
        ])->save();

        try {
            $session->update(['swap_request_id' => $otherSwap->id]);
            $this->fail('Changing the swap must resolve its learner again.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('teaching_side', $exception->errors());
        }

        $this->assertSame($swapRequest->id, $session->fresh()->swap_request_id);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    public function test_acceptance_does_not_recheck_or_change_a_balance_that_fell_after_proposal(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);
        $recipient->update(['skill_credits' => 0]);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))
            ->assertSessionHas('success', 'Session confirmed!');

        $this->assertSame(SkillSession::STATUS_CONFIRMED, $session->fresh()->status);
        $this->assertSame(1, $sender->fresh()->skill_credits);
        $this->assertSame(0, $recipient->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 2);
    }

    public function test_both_proposal_forms_show_the_learner_balance_for_each_direction(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $sender->update(['skill_credits' => 0]);
        $recipient->update(['skill_credits' => 3]);

        foreach (['skill-sessions.create', 'swap-requests.chat'] as $routeName) {
            $this->actingAs($sender)->get(route($routeName, $swapRequest))
                ->assertSee('Jiro teaches TypeScript to Justine — Learner Skill Credits: 3')
                ->assertSee('Justine teaches PHP to Jiro — Learner Skill Credits: 0')
                ->assertSee('Proposing a session does not spend credits.');
        }
    }

    public static function eligibleLearners(): array
    {
        return [
            'sender teacher proposes, one credit' => ['sender', true, 1],
            'recipient learner proposes, one credit' => ['sender', false, 1],
            'sender learner proposes, one credit' => ['recipient', true, 1],
            'recipient teacher proposes, one credit' => ['recipient', false, 1],
            'sender teacher proposes, three credits' => ['sender', true, 3],
            'recipient learner proposes, three credits' => ['sender', false, 3],
            'sender learner proposes, three credits' => ['recipient', true, 3],
            'recipient teacher proposes, three credits' => ['recipient', false, 3],
        ];
    }

    public static function cancelledProposalDirections(): array
    {
        return [
            'same learner' => ['sender', false],
            'changed learner' => ['recipient', false],
            'historical, recipient learns' => ['sender', true],
            'historical, sender learns' => ['recipient', true],
        ];
    }

    #[DataProvider('sessionDirections')]
    public function test_session_roles_follow_the_selected_skill_independently_of_the_proposer(string $teachingSide, bool $senderProposes): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $proposer = $senderProposes ? $sender : $recipient;

        $this->actingAs($proposer)->post(route('skill-sessions.store', $swapRequest), [
            ...$this->proposalPayload(),
            'teaching_side' => $teachingSide,
        ])->assertSessionHas('success');

        $session = $swapRequest->skillSession;
        $this->assertTrue($session->hasResolvedRoles());
        $this->assertSame($teachingSide, $session->teaching_side);
        $this->assertTrue(($teachingSide === 'sender' ? $sender : $recipient)->is($session->teacher));
        $this->assertTrue(($teachingSide === 'sender' ? $recipient : $sender)->is($session->learner));
        $this->assertTrue(($teachingSide === 'sender' ? $swapRequest->offeredSkill : $swapRequest->requestedSkill)->is($session->taughtSkill));
        $this->assertFalse($session->teacher->is($session->learner));

        $sender->teachingSkills()->detach();
        $recipient->teachingSkills()->detach();
        $this->assertSame($session->teacher->id, $session->fresh()->teacher->id);
        $this->actingAs($proposer)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee($session->teacher->name.' teaches '.$session->taughtSkill->name.' to '.$session->learner->name);
    }

    #[DataProvider('invalidTeachingSides')]
    public function test_proposals_require_a_valid_teaching_side(?string $teachingSide): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->from(route('swap-requests.chat', $swapRequest))
            ->followingRedirects()
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->proposalPayload(),
                'teaching_side' => $teachingSide,
            ])
            ->assertSee($teachingSide === null ? 'The teaching side field is required.' : 'The selected teaching side is invalid.')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('name="teaching_side"', false);

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_a_session_cannot_be_created_with_the_same_teacher_and_learner(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $swapRequest->update(['recipient_id' => $sender->id]);
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertSessionHasErrors(['teaching_side' => 'Choose a teacher from two different swap participants.']);

        $this->assertDatabaseCount('skill_sessions', 0);
        $session = new SkillSession(['teaching_side' => SkillSession::TEACHING_SIDE_SENDER]);
        $session->setRelation('swapRequest', $swapRequest);
        $this->assertFalse($session->hasResolvedRoles());
        $this->assertNull($session->teacher);
        $this->assertNull($session->learner);
    }

    public function test_historical_sessions_have_no_invented_roles_and_can_still_be_confirmed(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);
        SkillSession::whereKey($session->id)->update(['teaching_side' => null]);
        $session->refresh();

        $this->assertFalse($session->hasResolvedRoles());
        $this->assertNull($session->teacher);
        $this->assertNull($session->learner);
        $this->assertNull($session->taughtSkill);
        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))->assertSessionHas('success');
        $this->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Teaching direction was not recorded for this session.');
    }

    public function test_direct_session_creation_cannot_omit_the_teaching_direction(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->expectException(ValidationException::class);

        SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
        ]);
    }

    public static function sessionDirections(): array
    {
        return [
            'sender teaches and proposes' => ['sender', true],
            'sender teaches and recipient proposes' => ['sender', false],
            'recipient teaches and sender proposes' => ['recipient', true],
            'recipient teaches and proposes' => ['recipient', false],
        ];
    }

    public static function invalidTeachingSides(): array
    {
        return ['missing' => [null], 'invalid' => ['both']];
    }

    public function test_created_session_is_a_proposal_and_is_not_upcoming(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('success', 'Session proposal sent!');

        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'status' => SkillSession::STATUS_PROPOSED,
            'confirmed_at' => null,
        ]);
        $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('Schedule proposal pending')->assertSee('No upcoming sessions.');
    }

    public function test_other_participant_can_agree_and_confirmed_session_is_upcoming(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))
            ->assertRedirect(route('swap-requests.chat', $swapRequest))->assertSessionHas('success');
        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => now()->toDateTimeString(),
        ]);
        $this->actingAs($recipient)->get(route('dashboard'))
            ->assertSee('Session confirmed')->assertSee('September 12, 2026')
            ->assertSee('https://meet.example.test/private-room');
    }

    public function test_confirmed_session_cannot_be_declined_or_re_agreed(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))
            ->assertSessionHas('success');

        $this->actingAs($recipient)->patch(route('skill-sessions.decline', $session))
            ->assertSessionHas('info');

        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'status' => SkillSession::STATUS_CONFIRMED,
        ]);
    }

    public function test_proposer_cannot_respond_to_their_own_proposal(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($sender)->patch(route('skill-sessions.agree', $session))->assertForbidden();
        $this->actingAs($sender)->patch(route('skill-sessions.decline', $session))->assertForbidden();
        $this->assertDatabaseHas('skill_sessions', ['id' => $session->id, 'status' => SkillSession::STATUS_PROPOSED]);
    }

    public function test_unrelated_user_cannot_respond_to_a_proposal(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);
        $unrelatedUser = User::factory()->onboarded()->create();

        $this->actingAs($unrelatedUser)->patch(route('skill-sessions.agree', $session))->assertForbidden();
        $this->actingAs($unrelatedUser)->patch(route('skill-sessions.decline', $session))->assertForbidden();
    }

    public function test_other_participant_can_decline_then_repropose_using_same_row(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($recipient)->patch(route('skill-sessions.decline', $session))->assertSessionHas('success');
        $this->assertDatabaseHas('skill_sessions', ['id' => $session->id, 'status' => SkillSession::STATUS_CANCELLED]);

        $this->actingAs($recipient)->post(route('skill-sessions.store', $swapRequest), [
            ...$this->proposalPayload(),
            'teaching_side' => SkillSession::TEACHING_SIDE_RECIPIENT,
            'time' => '16:00',
        ])->assertSessionHas('success', 'Session proposal sent!');

        $this->assertDatabaseCount('skill_sessions', 1);
        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'scheduled_by' => $recipient->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_RECIPIENT,
            'scheduled_at' => '2026-09-12 16:00:00',
            'status' => SkillSession::STATUS_PROPOSED,
        ]);
        $this->assertTrue($session->fresh()->teacher->is($recipient));
        $this->assertTrue($session->fresh()->learner->is($sender));
    }

    public function test_second_active_proposal_and_changes_after_confirmation_are_blocked(): void
    {
        $this->travelTo('2026-09-09 08:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($recipient)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertSessionHas('info', 'This swap already has an active session proposal.');
        $session->update(['status' => SkillSession::STATUS_CONFIRMED, 'confirmed_at' => now()]);
        $this->actingAs($recipient)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertSessionHas('info', 'This swap already has an active session proposal.');
        $this->assertDatabaseCount('skill_sessions', 1);
    }

    public function test_only_other_participant_sees_response_controls(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, $sender);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Waiting for Justine')->assertDontSee('>Agree<', false)->assertDontSee('>Decline<', false);
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('>Agree<', false)->assertSee('>Decline<', false);
    }

    public function test_confirmed_chat_stays_active_and_hides_proposal_controls(): void
    {
        $this->travelTo('2026-09-09 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, $sender, [
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('SESSION CONFIRMED')->assertSee('Send Message')->assertDontSee('Propose Session');
    }

    public function test_stale_response_does_not_change_non_proposed_session(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender, ['status' => SkillSession::STATUS_CANCELLED]);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))
            ->assertSessionHas('info', 'This session proposal is no longer awaiting a response.');
        $this->assertDatabaseHas('skill_sessions', ['id' => $session->id, 'status' => SkillSession::STATUS_CANCELLED]);
    }

    public function test_pending_swap_cannot_create_or_respond_to_proposal(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest(['status' => SwapRequest::STATUS_PENDING]);
        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), $this->proposalPayload())
            ->assertForbidden();

        $session = $this->createSession($swapRequest, $sender);
        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))->assertForbidden();
    }

    /** @return array{User, User, SwapRequest} */
    private function createSwapRequest(array $attributes = []): array
    {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::create(['name' => 'TypeScript', 'is_approved' => true]);
        $requestedSkill = Skill::create(['name' => 'PHP', 'is_approved' => true]);

        return [$sender, $recipient, SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
            ...$attributes,
        ])];
    }

    private function createSession(SwapRequest $swapRequest, User $proposer, array $attributes = []): SkillSession
    {
        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $proposer->id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'scheduled_at' => '2026-09-12 15:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
            'status' => SkillSession::STATUS_PROPOSED,
            ...$attributes,
        ]);
    }

    /** @return array{teaching_side: string, date: string, time: string, duration_minutes: string, meeting_type: string, meeting_details: string} */
    private function proposalPayload(): array
    {
        return [
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'date' => '2026-09-12',
            'time' => '15:00',
            'duration_minutes' => '60',
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
        ];
    }
}
