<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SwapRequestResponseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_recipient_can_accept_a_pending_request(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 10:30:00');

        $response = $this->actingAs($recipient)
            ->patch(route('swap-requests.accept', $swapRequest));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Swap request accepted!');

        $this->assertDatabaseHas('swap_requests', [
            'id' => $swapRequest->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => '2026-09-09 10:30:00',
        ]);
    }

    public function test_recipient_can_reject_a_pending_request(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 11:45:00');

        $response = $this->actingAs($recipient)
            ->patch(route('swap-requests.reject', $swapRequest));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Swap request rejected.');

        $this->assertDatabaseHas('swap_requests', [
            'id' => $swapRequest->id,
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => '2026-09-09 11:45:00',
        ]);
    }

    public function test_sender_cannot_accept_their_sent_request(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertForbidden();

        $this->assertPendingAndUnanswered($swapRequest);
    }

    public function test_sender_cannot_reject_their_sent_request(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->patch(route('swap-requests.reject', $swapRequest))
            ->assertForbidden();

        $this->assertPendingAndUnanswered($swapRequest);
    }

    public function test_unrelated_user_cannot_respond_to_a_request(): void
    {
        [, , $swapRequest] = $this->createSwapRequest();
        $unrelatedUser = User::factory()->onboarded()->create();

        $this->actingAs($unrelatedUser)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertForbidden();

        $this->assertPendingAndUnanswered($swapRequest);
    }

    public function test_guest_cannot_respond_to_a_request(): void
    {
        [, , $swapRequest] = $this->createSwapRequest();

        $this->patch(route('swap-requests.accept', $swapRequest))
            ->assertRedirect(route('login'));

        $this->assertPendingAndUnanswered($swapRequest);
    }

    public function test_admin_cannot_participate_in_request_responses(): void
    {
        [, , $swapRequest] = $this->createSwapRequest();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertPendingAndUnanswered($swapRequest);
    }

    public function test_accepted_request_cannot_be_accepted_again(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => '2026-09-08 09:00:00',
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('info', 'This swap request has already been responded to.');

        $this->assertRequestState($swapRequest, SwapRequest::STATUS_ACCEPTED, '2026-09-08 09:00:00');
    }

    public function test_accepted_request_cannot_be_rejected(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => '2026-09-08 09:00:00',
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.reject', $swapRequest))
            ->assertSessionHas('info');

        $this->assertRequestState($swapRequest, SwapRequest::STATUS_ACCEPTED, '2026-09-08 09:00:00');
    }

    public function test_rejected_request_cannot_be_accepted(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => '2026-09-08 09:00:00',
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertSessionHas('info');

        $this->assertRequestState($swapRequest, SwapRequest::STATUS_REJECTED, '2026-09-08 09:00:00');
    }

    public function test_rejected_request_cannot_be_rejected_again(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => '2026-09-08 09:00:00',
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.reject', $swapRequest))
            ->assertSessionHas('info');

        $this->assertRequestState($swapRequest, SwapRequest::STATUS_REJECTED, '2026-09-08 09:00:00');
    }

    public function test_cancelled_request_cannot_be_accepted_or_rejected(): void
    {
        [, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_CANCELLED,
            'responded_at' => '2026-09-08 09:00:00',
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.accept', $swapRequest))
            ->assertSessionHas('info');

        $this->actingAs($recipient)
            ->patch(route('swap-requests.reject', $swapRequest))
            ->assertSessionHas('info');

        $this->assertRequestState($swapRequest, SwapRequest::STATUS_CANCELLED, '2026-09-08 09:00:00');
    }

    public function test_recipient_dashboard_shows_actions_only_for_pending_requests(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertSee('Incoming Swap Requests')
            ->assertSee('Jiro wants to swap skills with you.')
            ->assertSee(route('swap-requests.accept', $swapRequest), false)
            ->assertSee(route('swap-requests.reject', $swapRequest), false);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertDontSee(route('swap-requests.accept', $swapRequest), false)
            ->assertDontSee(route('swap-requests.reject', $swapRequest), false);
    }

    public function test_dashboard_escapes_swap_request_messages(): void
    {
        [, $recipient] = $this->createSwapRequest([
            'message' => '<script>alert("unsafe")</script>',
        ]);

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false);
    }

    public function test_accepted_swap_is_visible_to_both_participants(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertSee('Active Swaps')
            ->assertSee('Jiro')
            ->assertSee('Justine')
            ->assertSee('Status: Accepted')
            ->assertDontSee(route('swap-requests.accept', $swapRequest), false);

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertSee('Active Swaps')
            ->assertSee('Jiro')
            ->assertSee('Justine')
            ->assertSee('Status: Accepted')
            ->assertDontSee(route('swap-requests.accept', $swapRequest), false);
    }

    public function test_sender_dashboard_shows_rejected_request_status(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertSee('Sent Swap Requests')
            ->assertSee('Status: Rejected')
            ->assertDontSee(route('swap-requests.accept', $swapRequest), false);

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertDontSee(route('swap-requests.accept', $swapRequest), false)
            ->assertDontSee(route('swap-requests.reject', $swapRequest), false);
    }

    public function test_identical_request_can_be_sent_again_after_rejection(): void
    {
        [$sender, $recipient, , $offeredSkill, $requestedSkill] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), [
                'recipient_id' => $recipient->id,
                'offered_skill_id' => $offeredSkill->id,
                'requested_skill_id' => $requestedSkill->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('swap_requests', 2);
        $this->assertDatabaseHas('swap_requests', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);
    }

    public function test_pending_request_can_be_rejected_after_an_identical_historical_rejection(): void
    {
        [$sender, $recipient, , $offeredSkill, $requestedSkill] = $this->createSwapRequest([
            'status' => SwapRequest::STATUS_REJECTED,
            'responded_at' => now()->subDay(),
        ]);
        $pendingRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);

        $this->actingAs($recipient)
            ->patch(route('swap-requests.reject', $pendingRequest))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('swap_requests', 2);
        $this->assertDatabaseHas('swap_requests', [
            'id' => $pendingRequest->id,
            'status' => SwapRequest::STATUS_REJECTED,
        ]);
    }

    /**
     * @param  array{status?: string, responded_at?: mixed}  $attributes
     * @return array{User, User, SwapRequest, Skill, Skill}
     */
    private function createSwapRequest(array $attributes = []): array
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
            'status' => SwapRequest::STATUS_PENDING,
            ...$attributes,
        ]);

        return [$sender, $recipient, $swapRequest, $offeredSkill, $requestedSkill];
    }

    private function assertPendingAndUnanswered(SwapRequest $swapRequest): void
    {
        $this->assertRequestState($swapRequest, SwapRequest::STATUS_PENDING, null);
    }

    private function assertRequestState(
        SwapRequest $swapRequest,
        string $status,
        ?string $respondedAt
    ): void {
        $freshSwapRequest = $swapRequest->fresh();

        $this->assertSame($status, $freshSwapRequest->status);
        $this->assertSame($respondedAt, $freshSwapRequest->responded_at?->toDateTimeString());
    }
}
