<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SkillSessionProposalTest extends TestCase
{
    use LazilyRefreshDatabase;

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
            'time' => '16:00',
        ])->assertSessionHas('success', 'Session proposal sent!');

        $this->assertDatabaseCount('skill_sessions', 1);
        $this->assertDatabaseHas('skill_sessions', [
            'id' => $session->id,
            'scheduled_by' => $recipient->id,
            'scheduled_at' => '2026-09-12 16:00:00',
            'status' => SkillSession::STATUS_PROPOSED,
        ]);
    }

    public function test_second_active_proposal_and_changes_after_confirmation_are_blocked(): void
    {
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
            'scheduled_at' => '2026-09-12 15:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
            'status' => SkillSession::STATUS_PROPOSED,
            ...$attributes,
        ]);
    }

    /** @return array{date: string, time: string, duration_minutes: string, meeting_type: string, meeting_details: string} */
    private function proposalPayload(): array
    {
        return [
            'date' => '2026-09-12',
            'time' => '15:00',
            'duration_minutes' => '60',
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
        ];
    }
}
