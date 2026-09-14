<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use App\Notifications\SwapMessageReceived;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_swap_request_notifies_recipient_only(): void
    {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::create(['name' => 'Web Development', 'is_approved' => true]);
        $requestedSkill = Skill::create(['name' => 'Graphic Design', 'is_approved' => true]);
        $sender->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $sender->learningSkills()->attach($requestedSkill, ['type' => 'learn']);
        $recipient->teachingSkills()->attach($requestedSkill, ['type' => 'teach']);
        $recipient->learningSkills()->attach($offeredSkill, ['type' => 'learn']);
        $payload = [
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
        ];

        $this->actingAs($sender)->post(route('swap-requests.store'), $payload)->assertSessionHas('success');
        $this->actingAs($sender)->post(route('swap-requests.store'), $payload)->assertSessionHas('info');

        $this->assertSame(0, $sender->notifications()->count());
        $this->assertSame(1, $recipient->notifications()->count());
        $notification = $recipient->notifications()->first();
        $this->assertSame('swap_request.received', $notification->type);
        $this->assertSame('Jiro sent you a swap request', $notification->data['message']);
        $this->assertSame($sender->id, $notification->data['actor_id']);
        $this->assertSame(route('dashboard', absolute: false), $notification->data['url']);
    }

    public function test_accepting_notifies_sender_once(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest(['status' => SwapRequest::STATUS_PENDING]);

        $this->actingAs($recipient)->patch(route('swap-requests.accept', $swapRequest))->assertSessionHas('success');
        $this->actingAs($recipient)->patch(route('swap-requests.accept', $swapRequest))->assertSessionHas('info');

        $this->assertSame(0, $recipient->notifications()->count());
        $this->assertSame(1, $sender->notifications()->count());
        $notification = $sender->notifications()->first();
        $this->assertSame('swap_request.accepted', $notification->type);
        $this->assertSame('Justine accepted your swap request', $notification->data['message']);
        $this->assertSame(route('swap-requests.chat', $swapRequest, absolute: false), $notification->data['url']);
    }

    public function test_rejecting_notifies_sender(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest(['status' => SwapRequest::STATUS_PENDING]);

        $this->actingAs($recipient)->patch(route('swap-requests.reject', $swapRequest))->assertSessionHas('success');

        $this->assertSame(0, $recipient->notifications()->count());
        $this->assertSame('swap_request.rejected', $sender->notifications()->sole()->type);
    }

    public function test_chat_message_notifies_other_participant_without_message_content(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($recipient)->post(route('swap-requests.messages.store', $swapRequest), [
            'message' => 'My secret study notes',
        ])->assertRedirect(route('swap-requests.chat', $swapRequest));

        $this->assertSame(0, $recipient->notifications()->count());
        $notification = $sender->notifications()->sole();
        $this->assertSame('swap_message.received', $notification->type);
        $this->assertSame('Justine sent you a message', $notification->data['message']);
        $this->assertStringNotContainsString('secret', json_encode($notification->data));
    }

    public function test_session_proposal_notifies_other_participant_without_meeting_details(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->travelTo('2026-09-09 08:00:00');

        $this->actingAs($sender)->post(route('skill-sessions.store', $swapRequest), [
            'date' => '2026-09-12',
            'time' => '15:00',
            'duration_minutes' => '60',
            'meeting_type' => SkillSession::MEETING_TYPE_IN_PERSON,
            'meeting_details' => 'Room 204, Main Library',
        ])->assertSessionHas('success', 'Session proposal sent!');

        $this->assertSame(0, $sender->notifications()->count());
        $notification = $recipient->notifications()->sole();
        $this->assertSame('skill_session.proposed', $notification->type);
        $this->assertSame('Jiro proposed a session', $notification->data['message']);
        $this->assertStringNotContainsString('Room 204', json_encode($notification->data));
    }

    public function test_agreeing_notifies_proposer(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))->assertSessionHas('success');
        $this->actingAs($recipient)->patch(route('skill-sessions.agree', $session))->assertSessionHas('info');

        $this->assertSame(0, $recipient->notifications()->count());
        $notification = $sender->notifications()->sole();
        $this->assertSame('skill_session.confirmed', $notification->type);
        $this->assertSame('Justine agreed to your session proposal', $notification->data['message']);
    }

    public function test_declining_notifies_proposer(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender);

        $this->actingAs($recipient)->patch(route('skill-sessions.decline', $session))->assertSessionHas('success');

        $this->assertSame(0, $recipient->notifications()->count());
        $this->assertSame('skill_session.declined', $sender->notifications()->sole()->type);
    }

    public function test_first_completion_confirmation_notifies_other_participant(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender, [
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => '2026-09-10 08:00:00',
        ]);
        $this->travelTo('2026-09-13 08:00:00');

        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'You confirmed completion. Waiting for the other participant.');
        $this->actingAs($sender)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('info');

        $this->assertSame(0, $sender->notifications()->count());
        $notification = $recipient->notifications()->sole();
        $this->assertSame('skill_session.completion_requested', $notification->type);
        $this->assertSame('Jiro confirmed completion and is waiting for you', $notification->data['message']);
    }

    public function test_final_completion_confirmation_notifies_other_participant_of_completed_swap(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender, [
            'status' => SkillSession::STATUS_CONFIRMED,
            'confirmed_at' => '2026-09-10 08:00:00',
            'sender_confirmed_at' => '2026-09-12 16:00:00',
        ]);
        $this->travelTo('2026-09-13 08:00:00');

        $this->actingAs($recipient)->patch(route('skill-sessions.completion.store', $session))
            ->assertSessionHas('success', 'Skill swap completed! You each earned +1 Skill Credit.');

        $this->assertSame(0, $recipient->notifications()->count());
        $notification = $sender->notifications()->sole();
        $this->assertSame('skill_session.completed', $notification->type);
        $this->assertSame('Your skill swap with Justine is complete', $notification->data['message']);
        $this->assertSame(route('swap-requests.chat', $swapRequest, absolute: false), $notification->data['url']);
    }

    public function test_review_notifies_reviewee_without_review_content(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $session = $this->createSession($swapRequest, $sender, [
            'status' => SkillSession::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->actingAs($sender)->post(route('reviews.store', $session), [
            'rating' => 5,
            'comment' => 'Brilliant teacher',
        ])->assertSessionHas('success', 'Review submitted!');

        $this->assertSame(0, $sender->notifications()->count());
        $notification = $recipient->notifications()->sole();
        $this->assertSame('review.received', $notification->type);
        $this->assertStringNotContainsString('Brilliant', json_encode($notification->data));
    }

    public function test_bell_shows_unread_count_and_hides_badge_when_zero(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)->get(route('dashboard'))
            ->assertOk()->assertSee('aria-label="Notifications"', false)->assertDontSee('data-unread-badge', false);

        $this->notifyTimes($sender, $recipient, $swapRequest, 3);
        $sender->notifications()->first()->markAsRead();

        $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('data-unread-badge>2</b>', false)
            ->assertSee('Justine sent you a message');

        $this->notifyTimes($sender, $recipient, $swapRequest, 9);

        $this->assertSame(11, $sender->unreadNotifications()->count());
        $this->actingAs($sender)->get(route('dashboard'))->assertSee('data-unread-badge>9+</b>', false);
    }

    public function test_opening_notification_marks_it_read_and_redirects_to_destination(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->notifyTimes($sender, $recipient, $swapRequest, 2);
        [$opened, $untouched] = $sender->notifications()->get()->all();

        $this->actingAs($sender)->patch(route('notifications.read', $opened->id))
            ->assertRedirect(route('swap-requests.chat', $swapRequest, absolute: false));

        $this->assertNotNull($opened->fresh()->read_at);
        $this->assertNull($untouched->fresh()->read_at);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_mark_all_as_read_only_affects_current_user(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->notifyTimes($sender, $recipient, $swapRequest, 3);
        $this->notifyTimes($recipient, $sender, $swapRequest, 1);

        $this->actingAs($sender)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $sender->unreadNotifications()->count());
        $this->assertSame(3, $sender->notifications()->count());
        $this->assertSame(1, $recipient->unreadNotifications()->count());
    }

    public function test_user_cannot_read_or_list_another_users_notification(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $this->notifyTimes($recipient, $sender, $swapRequest, 1);
        $othersNotification = $recipient->notifications()->sole();

        $this->actingAs($sender)->patch(route('notifications.read', $othersNotification->id))->assertNotFound();
        $this->actingAs($sender)->get(route('notifications.index'))
            ->assertOk()->assertDontSee('Jiro sent you a message')->assertSee('You have no notifications yet.');

        $this->assertNull($othersNotification->fresh()->read_at);
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->patch(route('notifications.read-all'))->assertRedirect(route('login'));
    }

    private function notifyTimes(User $notifiable, User $actor, SwapRequest $swapRequest, int $times): void
    {
        foreach (range(1, $times) as $ignored) {
            $notifiable->notify(new SwapMessageReceived($actor, $swapRequest->id));
        }
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
}
