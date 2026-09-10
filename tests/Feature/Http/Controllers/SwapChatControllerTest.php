<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Models\User;
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
            ->assertOk()->assertSee('Jiro ↔ Justine')
            ->assertSee('You teach TypeScript')->assertSee('You learn PHP');

        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Jiro ↔ Justine')
            ->assertSee('You teach PHP')->assertSee('You learn TypeScript');
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
            ->assertDontSee('<script>', false);
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

    public function test_countdown_reports_the_session_as_ended_once_the_window_passes(): void
    {
        $this->travelTo('2026-09-10 08:00:00');
        [$sender, , $swapRequest] = $this->createSwapRequest();
        // Started 3 hours ago, 60 minute session: finished 2 hours ago.
        $this->createConfirmedSession($swapRequest, now()->copy()->subHours(3), 60);

        $this->actingAs($sender)
            ->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-state="ended"', false)
            ->assertSee('class="countdown countdown-ended"', false);
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
