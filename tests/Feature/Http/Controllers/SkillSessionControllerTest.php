<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SkillSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sender_can_open_scheduling_form_with_correct_perspective_and_availability(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        UserAvailability::create([
            'user_id' => $sender->id,
            'day' => 'wednesday',
            'time_period' => 'evening',
        ]);

        $this->actingAs($sender)
            ->get(route('skill-sessions.create', $swapRequest))
            ->assertSee('Propose a time for your accepted exchange with Justine.')
            ->assertSeeInOrder(['You teach', 'TypeScript', 'You learn', 'PHP'])
            ->assertSee('Wednesday')
            ->assertSee('Evening');
    }

    public function test_recipient_can_open_scheduling_form_with_correct_perspective(): void
    {
        [, $recipient, $swapRequest] = $this->createAcceptedSwapRequest();

        $this->actingAs($recipient)
            ->get(route('skill-sessions.create', $swapRequest))
            ->assertSee('Propose a time for your accepted exchange with Jiro.')
            ->assertSeeInOrder(['You teach', 'PHP', 'You learn', 'TypeScript']);
    }

    public function test_sender_can_schedule_an_accepted_swap(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $response = $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload());

        $response
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('success', 'Session proposal sent!');

        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => '2026-09-12 15:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
            'status' => SkillSession::STATUS_PROPOSED,
            'confirmed_at' => null,
            'completed_at' => null,
        ]);
    }

    public function test_recipient_can_schedule_an_accepted_swap(): void
    {
        [, $recipient, $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($recipient)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'meeting_type' => SkillSession::MEETING_TYPE_IN_PERSON,
                'meeting_details' => 'FEU Tech Library',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $recipient->id,
            'meeting_type' => SkillSession::MEETING_TYPE_IN_PERSON,
            'status' => SkillSession::STATUS_PROPOSED,
        ]);
    }

    public function test_pending_request_cannot_be_scheduled(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest([
            'status' => SwapRequest::STATUS_PENDING,
            'responded_at' => null,
        ]);

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_rejected_request_cannot_be_scheduled(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
        ]);

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_cancelled_request_cannot_be_scheduled(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest([
            'status' => SwapRequest::STATUS_CANCELLED,
        ]);

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_unrelated_user_cannot_open_or_submit_scheduling(): void
    {
        [, , $swapRequest] = $this->createAcceptedSwapRequest();
        $unrelatedUser = User::factory()->onboarded()->create();

        $this->actingAs($unrelatedUser)
            ->get(route('skill-sessions.create', $swapRequest))
            ->assertForbidden();

        $this->actingAs($unrelatedUser)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_guest_cannot_open_or_submit_scheduling(): void
    {
        [, , $swapRequest] = $this->createAcceptedSwapRequest();

        $this->get(route('skill-sessions.create', $swapRequest))
            ->assertRedirect(route('login'));

        $this->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_admin_cannot_schedule_a_session(): void
    {
        [, , $swapRequest] = $this->createAcceptedSwapRequest();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_incomplete_participant_cannot_schedule_a_session(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $sender->update(['onboarding_completed' => false]);

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertRedirect(route('onboarding.welcome'));

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_past_date_and_time_are_rejected(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'date' => '2026-09-08',
            ])
            ->assertSessionHasErrors([
                'date' => 'The session must be scheduled in the future.',
            ]);

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_malformed_date_and_time_are_rejected(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'date' => 'September 12',
                'time' => 'three o clock',
            ])
            ->assertSessionHasErrors(['date', 'time']);

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_any_duration_within_the_allowed_range_is_accepted(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'duration_minutes' => 47,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'duration_minutes' => 47,
        ]);
    }

    #[DataProvider('outOfRangeDurations')]
    public function test_duration_outside_the_allowed_range_is_rejected(int $duration): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'duration_minutes' => $duration,
            ])
            ->assertSessionHasErrors('duration_minutes');

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function outOfRangeDurations(): array
    {
        return [
            'zero' => [0],
            'negative' => [-30],
            'below minimum' => [SkillSession::MIN_DURATION_MINUTES - 1],
            'above maximum' => [SkillSession::MAX_DURATION_MINUTES + 1],
        ];
    }

    public function test_invalid_meeting_type_is_rejected(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'meeting_type' => 'telephone',
            ])
            ->assertSessionHasErrors('meeting_type');

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_overlong_meeting_details_are_rejected(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), [
                ...$this->validSchedulePayload(),
                'meeting_details' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('meeting_details');

        $this->assertDatabaseCount('skill_sessions', 0);
    }

    public function test_second_session_for_the_same_swap_is_prevented(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->createSkillSession($swapRequest, $sender, [
            'meeting_details' => 'Original private meeting details',
        ]);

        $this->actingAs($sender)
            ->post(route('skill-sessions.store', $swapRequest), $this->validSchedulePayload())
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('info', 'This swap already has an active session proposal.');

        $this->assertDatabaseCount('skill_sessions', 1);
        $this->assertDatabaseHas('skill_sessions', [
            'swap_request_id' => $swapRequest->id,
            'meeting_details' => 'Original private meeting details',
        ]);
    }

    public function test_scheduling_form_redirects_when_session_already_exists(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->createSkillSession($swapRequest, $sender);

        $this->actingAs($sender)
            ->get(route('skill-sessions.create', $swapRequest))
            ->assertRedirect(route('swap-requests.chat', $swapRequest))
            ->assertSessionHas('info', 'This swap already has an active session proposal.');
    }

    public function test_both_participants_can_see_scheduled_session_with_correct_perspective(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $this->createSkillSession($swapRequest, $sender);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertSee('Upcoming Sessions')
            ->assertSee('With Justine')
            ->assertSee('You teach: TypeScript')
            ->assertSee('You learn: PHP')
            ->assertSee('September 12, 2026')
            ->assertSee('3:00 PM')
            ->assertSee('https://meet.example.test/private-room')
            ->assertDontSee(route('skill-sessions.create', $swapRequest), false);

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertSee('Upcoming Sessions')
            ->assertSee('With Jiro')
            ->assertSee('You teach: PHP')
            ->assertSee('You learn: TypeScript')
            ->assertSee('https://meet.example.test/private-room')
            ->assertDontSee(route('skill-sessions.create', $swapRequest), false);
    }

    public function test_unrelated_user_cannot_see_session_or_meeting_details(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $this->createSkillSession($swapRequest, $sender);
        $unrelatedUser = User::factory()->onboarded()->create();

        $this->actingAs($unrelatedUser)
            ->get(route('dashboard'))
            ->assertDontSee('https://meet.example.test/private-room')
            ->assertDontSee('With Jiro')
            ->assertDontSee('With Justine');
    }

    public function test_meeting_details_are_escaped_on_dashboard(): void
    {
        [$sender, , $swapRequest] = $this->createAcceptedSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');
        $this->createSkillSession($swapRequest, $sender, [
            'meeting_details' => '<script>alert("private")</script>',
        ]);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false);
    }

    /**
     * @param  array{status?: string, responded_at?: mixed}  $attributes
     * @return array{User, User, SwapRequest, Skill, Skill}
     */
    private function createAcceptedSwapRequest(array $attributes = []): array
    {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::create(['name' => 'TypeScript', 'is_approved' => true]);
        $requestedSkill = Skill::create(['name' => 'PHP', 'is_approved' => true]);

        $sender->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $sender->learningSkills()->attach($requestedSkill, ['type' => 'learn']);
        $recipient->teachingSkills()->attach($requestedSkill, ['type' => 'teach']);
        $recipient->learningSkills()->attach($offeredSkill, ['type' => 'learn']);

        $swapRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'message' => 'Hi Justine! Let us swap skills.',
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
            ...$attributes,
        ]);

        return [$sender, $recipient, $swapRequest, $offeredSkill, $requestedSkill];
    }

    /**
     * @param  array{meeting_details?: string}  $attributes
     */
    private function createSkillSession(
        SwapRequest $swapRequest,
        User $scheduledBy,
        array $attributes = []
    ): SkillSession {
        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $scheduledBy->id,
            'scheduled_at' => '2026-09-12 15:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'meeting_details' => 'https://meet.example.test/private-room',
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => '2026-09-09 08:00:00',
            ...$attributes,
        ]);
    }

    /**
     * @return array{date: string, time: string, duration_minutes: string, meeting_type: string, meeting_details: string}
     */
    private function validSchedulePayload(): array
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
