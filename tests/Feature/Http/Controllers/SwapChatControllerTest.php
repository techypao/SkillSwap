<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Models\User;
use App\Models\UserAvailability;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SwapChatControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_both_participants_can_open_an_accepted_swap_chat_with_their_own_perspective(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('<h1 class="header-name">Justine</h1>', false)
            ->assertSeeInOrder(['You teach:', 'TypeScript', 'You learn:', 'PHP']);

        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('<h1 class="header-name">Jiro</h1>', false)
            ->assertSeeInOrder(['You teach:', 'PHP', 'You learn:', 'TypeScript']);
    }

    public function test_header_shows_other_participants_program_and_year(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $program = Program::create(['name' => 'BS Information Technology', 'abbreviation' => 'BSIT', 'is_active' => true]);
        $recipient->update(['program_id' => $program->id, 'year_level' => 4]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('BS Information Technology')->assertSee('Year 4');
    }

    public function test_no_session_state_shows_propose_action_availability_and_common_overlap(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        foreach ([['monday', 'morning'], ['wednesday', 'afternoon']] as [$day, $period]) {
            UserAvailability::create(['user_id' => $sender->id, 'day' => $day, 'time_period' => $period]);
        }
        foreach ([['tuesday', 'afternoon'], ['wednesday', 'afternoon']] as [$day, $period]) {
            UserAvailability::create(['user_id' => $recipient->id, 'day' => $day, 'time_period' => $period]);
        }

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-session-stage="discussing"', false)->assertSee('Discussing Schedule')
            ->assertSee('No session proposed yet.')
            ->assertSee('Propose Session')->assertSee('action="'.route('skill-sessions.store', $swapRequest).'"', false)
            ->assertSeeInOrder(['Common availability', '✓ Wednesday • Afternoon', 'Your availability', 'Monday • Morning', "Justine's availability", 'Tuesday • Afternoon'])
            ->assertSee(route('swap-requests.messages.store', $swapRequest), false)->assertSee('Send Message');
    }

    public function test_common_availability_is_hidden_when_there_is_no_overlap(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        UserAvailability::create(['user_id' => $sender->id, 'day' => 'monday', 'time_period' => 'morning']);
        UserAvailability::create(['user_id' => $recipient->id, 'day' => 'monday', 'time_period' => 'evening']);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertDontSee('Common availability')->assertSee('Monday • Evening');
    }

    public function test_declined_proposal_returns_to_the_no_session_state(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, ['status' => SkillSession::STATUS_CANCELLED]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Discussing Schedule')->assertSee('Propose Session')
            ->assertDontSee('SESSION PROPOSAL');
    }

    public function test_proposed_state_differs_for_proposer_and_other_participant(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, [
            'scheduled_at' => '2026-09-16 15:00:00',
            'status' => SkillSession::STATUS_PROPOSED,
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Proposal Pending')->assertSee('SESSION PROPOSAL')
            ->assertSee('September 16, 2026')->assertSee('3:00 PM – 4:00 PM')->assertSee('Proposed by Jiro')
            ->assertSee('Waiting for Justine')
            ->assertDontSee('>Agree<', false)->assertDontSee('>Decline<', false)
            ->assertDontSee('Propose Session')->assertDontSee('data-availability', false);

        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Needs Your Response')
            ->assertSee(route('skill-sessions.agree', $swapRequest->skillSession), false)
            ->assertSee(route('skill-sessions.decline', $swapRequest->skillSession), false)
            ->assertSee('>Agree<', false)->assertSee('>Decline<', false)
            ->assertDontSee('Waiting for Jiro');
    }

    public function test_confirmed_future_session_shows_details_without_completion_action(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->addDays(2), 90);
        $swapRequest->skillSession->update(['meeting_details' => 'https://meet.example.test/room']);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Session Confirmed')->assertSee('SESSION CONFIRMED')
            ->assertSee('90 minutes · Online')
            ->assertSee('href="https://meet.example.test/room"', false)
            ->assertSee('Completion can be confirmed after the session starts.')
            ->assertDontSee('Confirm Session Completed')->assertDontSee('>Agree<', false)
            ->assertSee('Send Message');
    }

    public function test_non_url_meeting_details_are_escaped_and_not_linked(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->addDays(2), 60);
        $swapRequest->skillSession->update(['meeting_details' => 'javascript:alert(1) <b>Room 4</b>']);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('&lt;b&gt;Room 4&lt;/b&gt;', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_started_confirmed_session_shows_completion_action(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->subMinutes(10), 60);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Awaiting Completion')->assertSee('Session Completion')
            ->assertSee('Confirm Session Completed')
            ->assertSee(route('skill-sessions.completion.store', $swapRequest->skillSession), false)
            ->assertDontSee('Completion can be confirmed after the session starts.');
    }

    public function test_completed_workspace_is_read_only(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, [
            'scheduled_at' => now()->copy()->subHours(3),
            'status' => SkillSession::STATUS_COMPLETED,
            'confirmed_at' => now()->copy()->subDay(),
            'sender_confirmed_at' => now()->copy()->subHour(),
            'recipient_confirmed_at' => now()->copy()->subHour(),
            'completed_at' => now()->copy()->subHour(),
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('data-session-stage="completed"', false)
            ->assertSee('SKILL SWAP COMPLETED')->assertSee('+1 Skill Credit earned')
            ->assertSee('TypeScript ↔ PHP')->assertSee('This conversation is closed.')
            ->assertSee('Leave Review')
            ->assertDontSee(route('swap-requests.messages.store', $swapRequest), false)->assertDontSee('Send Message')
            ->assertDontSee('Propose Session')->assertDontSee('>Agree<', false)
            ->assertDontSee('>Decline<', false)->assertDontSee('Confirm Session Completed');
    }

    public function test_chat_offers_a_collapsed_inline_proposal_form_instead_of_the_standalone_page(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-proposal-toggle', false)->assertSee('aria-expanded="false"', false)
            ->assertSee('action="'.route('skill-sessions.store', $swapRequest).'"', false)
            ->assertSeeInOrder(['name="date"', 'name="time"', 'name="duration_minutes"', 'name="meeting_type"', 'name="meeting_details"'], false)
            ->assertSee('Send Proposal')->assertSee('data-proposal-cancel', false)
            ->assertDontSee('href="'.route('skill-sessions.create', $swapRequest).'"', false)
            ->assertDontSee('class="field-error"', false);
    }

    public function test_valid_inline_proposal_creates_the_session_and_returns_to_chat(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->post(route('skill-sessions.store', $swapRequest), $this->inlineProposalPayload())
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('success', 'Session proposal sent!');

        $this->assertDatabaseCount('skill_sessions', 1);
        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => '2026-09-16 15:00:00',
            'duration_minutes' => 90,
            'meeting_type' => SkillSession::MEETING_TYPE_IN_PERSON,
            'meeting_details' => 'Library room 2',
            'status' => SkillSession::STATUS_PROPOSED,
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Waiting for Justine to respond.')
            ->assertDontSee('>Agree<', false)
            ->assertDontSee('action="'.route('skill-sessions.store', $swapRequest).'"', false);
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('>Agree<', false)->assertSee('>Decline<', false);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $swapRequest->skillSession))
            ->assertRedirect(route('swap-requests.chat', $swapRequest));
        $this->assertSame(SkillSession::STATUS_CONFIRMED, $swapRequest->skillSession->fresh()->status);
    }

    public function test_invalid_inline_proposal_returns_to_chat_with_inline_errors_and_old_input(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $payload = [
            ...$this->inlineProposalPayload(),
            'date' => '2026-09-01',
            'meeting_details' => 'Room <b>7</b>',
        ];

        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->post(route('skill-sessions.store', $swapRequest), $payload)
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHasErrors('date');

        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->followingRedirects()
            ->post(route('skill-sessions.store', $swapRequest), $payload)
            ->assertOk()
            ->assertSee('<h1 class="header-name">Justine</h1>', false)
            ->assertSee('<p class="field-error">The session must be scheduled in the future.</p>', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('value="2026-09-01"', false)->assertSee('value="15:00"', false)
            ->assertSee('name="duration_minutes"', false)->assertSee('value="90"', false)
            ->assertSee('value="in_person" selected', false)
            ->assertSee('Room &lt;b&gt;7&lt;/b&gt;', false)
            ->assertDontSee('<div class="alert alert-error">', false);

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_either_participant_can_propose_a_custom_duration(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('type="number"', false)
            ->assertSee('min="'.SkillSession::MIN_DURATION_MINUTES.'"', false)
            ->assertSee('max="'.SkillSession::MAX_DURATION_MINUTES.'"', false)
            ->assertSee('data-duration-chip="45"', false);

        $this->actingAs($recipient)
            ->from(route('swap-requests.chat', $swapRequest))
            ->post(route('skill-sessions.store', $swapRequest), [...$this->inlineProposalPayload(), 'duration_minutes' => '75'])
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $recipient->id,
            'duration_minutes' => 75,
        ]);
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('75 minutes')->assertSee('3:00 PM – 4:15 PM');
    }

    public function test_out_of_range_custom_duration_shows_inline_error_and_keeps_value(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->followingRedirects()
            ->post(route('skill-sessions.store', $swapRequest), [...$this->inlineProposalPayload(), 'duration_minutes' => '481'])
            ->assertOk()
            ->assertSee('value="481"', false)
            ->assertSee('<p class="field-error">The duration minutes field must not be greater than 480.</p>', false);

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_missing_inline_proposal_fields_show_field_specific_errors(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->followingRedirects()
            ->post(route('skill-sessions.store', $swapRequest), ['meeting_type' => 'carrier_pigeon'])
            ->assertOk()
            ->assertSee('The date field is required.')->assertSee('The time field is required.')
            ->assertSee('The duration minutes field is required.')->assertSee('The selected meeting type is invalid.');

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_unauthorized_users_still_cannot_propose_from_chat(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs(User::factory()->onboarded()->create())
            ->from(route('swap-requests.chat', $swapRequest))
            ->post(route('skill-sessions.store', $swapRequest), $this->inlineProposalPayload())
            ->assertForbidden();

        [$pendingSender, , $pendingSwapRequest] = $this->createSwapRequest(['status' => SwapRequest::STATUS_PENDING]);
        $this->actingAs($pendingSender)
            ->post(route('skill-sessions.store', $pendingSwapRequest), $this->inlineProposalPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_decline_after_inline_proposal_brings_back_the_inline_form(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->actingAs($sender)
            ->from(route('swap-requests.chat', $swapRequest))
            ->post(route('skill-sessions.store', $swapRequest), $this->inlineProposalPayload());

        $this->actingAs($recipient)->patch(route('skill-sessions.decline', $swapRequest->skillSession))
            ->assertRedirect(route('swap-requests.chat', $swapRequest));

        $this->assertSame(SkillSession::STATUS_CANCELLED, $swapRequest->skillSession->fresh()->status);
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Propose Session')
            ->assertSee('action="'.route('skill-sessions.store', $swapRequest).'"', false)
            ->assertDontSee('>Agree<', false);
    }

    public function test_workspace_does_not_expose_session_or_availability_to_unrelated_users(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createSession($swapRequest, ['meeting_details' => 'https://meet.example.test/secret']);

        $this->actingAs(User::factory()->onboarded()->create())
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertForbidden()->assertDontSee('https://meet.example.test/secret');
    }

    public function test_non_accepted_swaps_cannot_be_opened_as_chat(): void
    {
        foreach ([SwapRequest::STATUS_PENDING, SwapRequest::STATUS_REJECTED, SwapRequest::STATUS_CANCELLED] as $status) {
            [$sender, , $swapRequest] = $this->createSwapRequest(['status' => $status]);
            $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))->assertForbidden();
        }
    }

    public function test_guest_unrelated_admin_and_incomplete_participant_cannot_open_chat(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->get(route('swap-requests.chat', $swapRequest))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->onboarded()->create())
            ->get(route('swap-requests.chat', $swapRequest))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('swap-requests.chat', $swapRequest))->assertRedirect(route('admin.dashboard'));

        $sender->update(['onboarding_completed' => false]);
        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertRedirect(route('onboarding.welcome'));
    }

    public function test_sender_and_recipient_can_send_trimmed_messages(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => '  Are you free Saturday?  ',
        ])->assertRedirect(route('swap-requests.chat', $swapRequest));
        $this->actingAs($recipient)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => 'Yes, 3 PM works.',
        ])->assertRedirect(route('swap-requests.chat', $swapRequest));

        $this->assertDatabaseHas('swap_messages', [
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $sender->id,
            'message' => 'Are you free Saturday?',
        ]);
        $this->assertDatabaseHas('swap_messages', [
            'sender_id' => $recipient->id,
            'message' => 'Yes, 3 PM works.',
        ]);
        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Are you free Saturday?');
    }

    public function test_messages_are_required_and_limited_to_two_thousand_characters(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => '   ',
        ])->assertSessionHasErrors('message');
        $this->actingAs($sender)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => str_repeat('a', 2001),
        ])->assertSessionHasErrors('message');

        $this->assertDatabaseCount('swap_messages', 0);
    }

    public function test_non_participants_and_non_accepted_swaps_cannot_receive_messages(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->actingAs(User::factory()->onboarded()->create())
            ->post(route('swap-requests.messages.store', $swapRequest), ['message' => 'Intrusion'])
            ->assertForbidden();

        $swapRequest->update(['status' => SwapRequest::STATUS_REJECTED]);
        $this->actingAs($sender)
            ->post(route('swap-requests.messages.store', $swapRequest), ['message' => 'Too late'])
            ->assertForbidden();

        $this->assertDatabaseCount('swap_messages', 0);
    }

    public function test_chat_orders_messages_and_escapes_html(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        SwapMessage::create([
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $recipient->id,
            'message' => '<script>alert("xss")</script>',
            'created_at' => '2026-09-09 09:00:00',
        ]);
        SwapMessage::create([
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $sender->id,
            'message' => 'Second message',
            'created_at' => '2026-09-09 10:00:00',
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSeeInOrder(['&lt;script&gt;', 'Second message'], false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_messages_from_another_swap_are_not_exposed(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        [, , $otherSwapRequest] = $this->createSwapRequest();
        SwapMessage::create([
            'swap_request_id' => $otherSwapRequest->id,
            'sender_id' => $otherSwapRequest->sender_id,
            'message' => 'Private message from another swap',
        ]);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertDontSee('Private message from another swap');
    }

    public function test_dashboard_links_accepted_swaps_to_chat(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('Discussing schedule')->assertSee('Open Chat')
            ->assertSee(route('swap-requests.chat', $swapRequest), false);
    }

    public function test_confirmed_session_shows_a_countdown_before_it_starts(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->addHours(3), 60);

        $this->actingAs($sender)
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-state="upcoming"', false)
            ->assertSee('class="countdown countdown-upcoming"', false);
    }

    public function test_countdown_switches_to_time_remaining_while_the_session_runs(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        // Started 20 minutes ago, 60 minute session: 40 minutes left.
        $this->createConfirmedSession($swapRequest, now()->copy()->subMinutes(20), 60);

        $this->actingAs($sender)
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-state="live"', false)
            ->assertSee('class="countdown countdown-live"', false);
    }

    public function test_ended_session_leads_with_awaiting_completion_instead_of_an_ended_countdown(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        // Started 3 hours ago, 60 minute session: finished 2 hours ago.
        $this->createConfirmedSession($swapRequest, now()->copy()->subHours(3), 60);

        $this->actingAs($sender)
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('class="primary-state primary-state-awaiting"', false)
            ->assertSee('AWAITING COMPLETION')
            ->assertDontSee('SESSION CONFIRMED')
            ->assertDontSee('data-session-countdown', false)
            ->assertSee('Confirm Session Completed');
    }

    public function test_proposed_session_does_not_show_a_countdown_yet(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => now()->copy()->addHours(3),
            'duration_minutes' => 60,
            'meeting_type' => 'online',
            'status' => SkillSession::STATUS_PROPOSED,
        ]);

        $this->actingAs($sender)
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('SESSION PROPOSAL')
            ->assertDontSee('data-session-countdown', false);
    }

    /** @return array<string, string> */
    private function inlineProposalPayload(): array
    {
        return [
            'date' => '2026-09-16',
            'time' => '15:00',
            'duration_minutes' => '90',
            'meeting_type' => SkillSession::MEETING_TYPE_IN_PERSON,
            'meeting_details' => 'Library room 2',
        ];
    }

    private function createSession(SwapRequest $swapRequest, array $attributes = []): SkillSession
    {
        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $swapRequest->sender_id,
            'scheduled_at' => now()->copy()->addDay(),
            'duration_minutes' => 60,
            'meeting_type' => 'online',
            'status' => SkillSession::STATUS_PROPOSED,
            ...$attributes,
        ]);
    }

    private function createConfirmedSession(
        SwapRequest $swapRequest,
        CarbonInterface $scheduledAt,
        int $durationMinutes
    ): SkillSession {
        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $swapRequest->sender_id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'meeting_type' => 'online',
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /** @return array{User, User, SwapRequest} */
    private function createSwapRequest(array $attributes = []): array
    {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::firstOrCreate(['name' => 'TypeScript'], ['is_approved' => true]);
        $requestedSkill = Skill::firstOrCreate(['name' => 'PHP'], ['is_approved' => true]);

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
}
