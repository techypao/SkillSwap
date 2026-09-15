<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MatchControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_current_online_session_is_linked_from_the_navigation_bar(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        [$currentUser, $matchedUser, $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->subMinutes(20), 60);

        $expectedUrl = route('swap-requests.chat', [
            'swapRequest' => $swapRequest,
            'panel' => 'call',
        ]);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $matchedUser))
            ->assertSee('aria-label="Open current session"', false)
            ->assertSee('href="'.$expectedUrl.'"', false);
    }

    public function test_current_in_person_session_opens_the_session_details_panel(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        [$currentUser, $matchedUser, $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession(
            $swapRequest,
            now()->copy()->subMinutes(20),
            60,
            SkillSession::MEETING_TYPE_IN_PERSON
        );

        $expectedUrl = route('swap-requests.chat', [
            'swapRequest' => $swapRequest,
            'panel' => 'session',
        ]);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $matchedUser))
            ->assertSee('href="'.$expectedUrl.'"', false);
    }

    public function test_future_and_ended_sessions_are_not_linked_from_the_navigation_bar(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        [$currentUser, $matchedUser, $futureSwapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($futureSwapRequest, now()->copy()->addMinute(), 60);
        [, , $endedSwapRequest] = $this->createSwapRequest($currentUser);
        $this->createConfirmedSession($endedSwapRequest, now()->copy()->subMinutes(61), 60);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $matchedUser))
            ->assertDontSee('aria-label="Open current session"', false);
    }

    public function test_another_users_current_session_is_not_linked_from_the_navigation_bar(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $currentUser = User::factory()->onboarded()->create();
        [, $matchedUser, $swapRequest] = $this->createSwapRequest();
        $this->createConfirmedSession($swapRequest, now()->copy()->subMinutes(20), 60);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $matchedUser))
            ->assertDontSee('aria-label="Open current session"', false);
    }

    /** @return array{User, User, SwapRequest} */
    private function createSwapRequest(?User $sender = null): array
    {
        $sender ??= User::factory()->onboarded()->create();
        $recipient = User::factory()->onboarded()->create();
        $offeredSkill = Skill::create(['name' => fake()->unique()->word(), 'is_approved' => true]);
        $requestedSkill = Skill::create(['name' => fake()->unique()->word(), 'is_approved' => true]);

        return [$sender, $recipient, SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ])];
    }

    private function createConfirmedSession(
        SwapRequest $swapRequest,
        CarbonInterface $scheduledAt,
        int $durationMinutes,
        string $meetingType = SkillSession::MEETING_TYPE_ONLINE
    ): SkillSession {
        return SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $swapRequest->sender_id,
            'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'meeting_type' => $meetingType,
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }
}
