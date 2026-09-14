<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\SwapMessageController;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SwapMessagePollingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_participant_retrieves_only_messages_newer_than_after_in_chronological_order(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();
        $first = $this->createMessage($swapRequest, $sender, 'First');
        $second = $this->createMessage($swapRequest, $recipient, 'Second');
        $third = $this->createMessage($swapRequest, $sender, 'Third');

        $this->actingAs($recipient)
            ->getJson(route('swap-requests.messages.index', ['swapRequest' => $swapRequest, 'after' => $first->id]))
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('closed', false)
            ->assertJsonPath('messages.0.id', $second->id)
            ->assertJsonPath('messages.0.message', 'Second')
            ->assertJsonPath('messages.0.sender_name', 'Justine')
            ->assertJsonPath('messages.0.is_mine', true)
            ->assertJsonPath('messages.1.id', $third->id)
            ->assertJsonPath('messages.1.sender_name', 'Jiro')
            ->assertJsonPath('messages.1.is_mine', false)
            ->assertJsonStructure(['messages' => [['id', 'sender_id', 'sender_name', 'is_mine', 'message', 'created_at', 'created_at_label']], 'closed']);

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', ['swapRequest' => $swapRequest, 'after' => $third->id]))
            ->assertOk()->assertJsonCount(0, 'messages');
    }

    public function test_without_after_messages_are_returned_up_to_the_poll_limit(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        foreach (range(1, SwapMessageController::POLL_LIMIT + 5) as $number) {
            $this->createMessage($swapRequest, $sender, "Message {$number}");
        }

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', $swapRequest))
            ->assertOk()
            ->assertJsonCount(SwapMessageController::POLL_LIMIT, 'messages')
            ->assertJsonPath('messages.0.message', 'Message 1');
    }

    public function test_message_text_is_returned_as_plain_data_for_safe_rendering(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createMessage($swapRequest, $sender, '<img src=x onerror=alert(1)>');

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', $swapRequest))
            ->assertOk()->assertJsonPath('messages.0.message', '<img src=x onerror=alert(1)>');
    }

    public function test_invalid_after_value_is_rejected(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', ['swapRequest' => $swapRequest, 'after' => 'abc']))
            ->assertUnprocessable()->assertJsonValidationErrors('after');
    }

    public function test_messages_from_another_swap_are_never_returned(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        [, , $otherSwapRequest] = $this->createSwapRequest();
        $this->createMessage($otherSwapRequest, $otherSwapRequest->sender, 'Private message from another swap');
        $own = $this->createMessage($swapRequest, $sender, 'Own swap message');

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', ['swapRequest' => $swapRequest, 'after' => 0]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $own->id)
            ->assertDontSee('Private message from another swap');
    }

    public function test_guest_unrelated_user_and_non_accepted_swap_cannot_poll(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createMessage($swapRequest, $sender, 'Secret');

        $this->getJson(route('swap-requests.messages.index', $swapRequest))->assertUnauthorized();

        $this->actingAs(User::factory()->onboarded()->create())
            ->getJson(route('swap-requests.messages.index', $swapRequest))
            ->assertForbidden()->assertDontSee('Secret');

        $swapRequest->update(['status' => SwapRequest::STATUS_REJECTED]);
        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', $swapRequest))
            ->assertForbidden();
    }

    public function test_completed_conversation_reports_closed_and_still_rejects_new_messages(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createMessage($swapRequest, $sender, 'Historical message');
        SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 60,
            'meeting_type' => 'online',
            'status' => SkillSession::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->actingAs($sender)
            ->getJson(route('swap-requests.messages.index', $swapRequest))
            ->assertOk()->assertJsonPath('closed', true)
            ->assertJsonPath('messages.0.message', 'Historical message');

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()->assertSee('Historical message')
            ->assertDontSee('data-poll-url="', false);

        $this->actingAs($sender)
            ->post(route('swap-requests.messages.store', $swapRequest), ['message' => 'Must not be stored'])
            ->assertSessionHas('info', 'This conversation is closed.');
        $this->assertDatabaseMissing('swap_messages', ['message' => 'Must not be stored']);
    }

    public function test_active_chat_renders_polling_hooks_with_the_latest_message_id(): void
    {
        [$sender, , $swapRequest] = $this->createSwapRequest();
        $this->createMessage($swapRequest, $sender, 'Earlier');
        $latest = $this->createMessage($swapRequest, $sender, 'Latest');

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-poll-url="'.route('swap-requests.messages.index', $swapRequest).'"', false)
            ->assertSee('data-last-message-id="'.$latest->id.'"', false)
            ->assertSee('data-message-id="'.$latest->id.'"', false);
    }

    public function test_sent_message_is_visible_to_the_other_participant_via_polling(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwapRequest();

        $this->actingAs($sender)
            ->post(route('swap-requests.messages.store', $swapRequest), ['message' => '  Are you free Saturday?  '])
            ->assertRedirect(route('swap-requests.chat', $swapRequest));

        $this->actingAs($recipient)
            ->getJson(route('swap-requests.messages.index', ['swapRequest' => $swapRequest, 'after' => 0]))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Are you free Saturday?')
            ->assertJsonPath('messages.0.is_mine', false);
    }

    private function createMessage(SwapRequest $swapRequest, User $sender, string $text): SwapMessage
    {
        return SwapMessage::create([
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $sender->id,
            'message' => $text,
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
