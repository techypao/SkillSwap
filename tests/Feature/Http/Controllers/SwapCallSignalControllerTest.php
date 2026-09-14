<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CallSignal;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SwapCallSignalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SDP = "v=0\r\no=- 4611731400430051336 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n";

    public function test_participants_exchange_signals_and_see_each_others_presence(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwap();

        $senderJoin = $this->actingAs($sender)
            ->postJson($this->signalUrl($swapRequest), ['type' => 'join'])
            ->assertCreated()
            ->assertJsonPath('peer_in_call', false);
        $this->assertSame($senderJoin->json('id'), $senderJoin->json('cursor'));

        $this->actingAs($recipient)
            ->getJson($this->signalUrl($swapRequest, ['after' => 0]))
            ->assertOk()
            ->assertJsonPath('peer_in_call', true)
            ->assertJsonPath('signals.0.type', 'join');

        $this->actingAs($recipient)
            ->postJson($this->signalUrl($swapRequest), ['type' => 'join'])
            ->assertCreated()
            ->assertJsonPath('peer_in_call', true);

        $offer = $this->actingAs($sender)
            ->postJson($this->signalUrl($swapRequest), ['type' => 'offer', 'payload' => ['call_id' => 'call-abc', 'sdp' => self::SDP]])
            ->assertCreated();

        $this->actingAs($recipient)
            ->getJson($this->signalUrl($swapRequest, ['after' => $senderJoin->json('id')]))
            ->assertOk()
            ->assertJsonCount(1, 'signals')
            ->assertJsonPath('signals.0.type', 'offer')
            ->assertJsonPath('signals.0.payload.call_id', 'call-abc')
            ->assertJsonPath('signals.0.payload.sdp', self::SDP)
            ->assertJsonPath('cursor', $offer->json('id'));

        $this->actingAs($recipient)->postJson($this->signalUrl($swapRequest), ['type' => 'answer', 'payload' => ['call_id' => 'call-abc', 'sdp' => self::SDP]])->assertCreated();
        $this->actingAs($recipient)->postJson($this->signalUrl($swapRequest), [
            'type' => 'candidate',
            'payload' => ['call_id' => 'call-abc', 'candidate' => ['candidate' => 'candidate:1 1 udp 2122260223 192.168.1.2 54400 typ host', 'sdpMid' => '0', 'sdpMLineIndex' => 0]],
        ])->assertCreated();
        $this->actingAs($recipient)->postJson($this->signalUrl($swapRequest), ['type' => 'media', 'payload' => ['audio' => false, 'video' => true]])->assertCreated();

        $this->actingAs($sender)
            ->getJson($this->signalUrl($swapRequest, ['after' => $offer->json('id')]))
            ->assertOk()
            ->assertJsonCount(3, 'signals')
            ->assertJsonPath('signals.0.type', 'answer')
            ->assertJsonPath('signals.1.type', 'candidate')
            ->assertJsonPath('signals.1.payload.candidate.sdpMLineIndex', 0)
            ->assertJsonPath('signals.2.type', 'media')
            ->assertJsonPath('signals.2.payload', ['audio' => false, 'video' => true]);
    }

    public function test_participants_never_receive_their_own_signals_and_only_newer_ones(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwap();
        $first = CallSignal::factory()->between($swapRequest, $recipient)->create();
        $second = CallSignal::factory()->between($swapRequest, $recipient)->offer()->create();
        CallSignal::factory()->between($swapRequest, $sender)->offer()->create();
        $third = CallSignal::factory()->between($swapRequest, $recipient)->create(['type' => CallSignal::TYPE_LEAVE]);

        $this->actingAs($sender)
            ->getJson($this->signalUrl($swapRequest, ['after' => $first->id]))
            ->assertOk()
            ->assertJsonCount(2, 'signals')
            ->assertJsonPath('signals.0.id', $second->id)
            ->assertJsonPath('signals.1.id', $third->id)
            ->assertJsonPath('cursor', $third->id);
    }

    public function test_signals_from_another_swap_are_never_returned(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();
        [, $otherRecipient, $otherSwapRequest] = $this->createSwap();
        CallSignal::factory()->between($otherSwapRequest, $otherRecipient)->offer('other-swap-call')->create();

        $this->actingAs($sender)
            ->getJson($this->signalUrl($swapRequest, ['after' => 0]))
            ->assertOk()
            ->assertJsonCount(0, 'signals')
            ->assertDontSee('other-swap-call');
    }

    public function test_guests_cannot_access_signaling(): void
    {
        [, , $swapRequest] = $this->createSwap();

        $this->getJson($this->signalUrl($swapRequest))->assertUnauthorized();
        $this->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertUnauthorized();
    }

    public function test_unrelated_users_cannot_read_or_send_signals_or_see_presence(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();
        [$outsider] = $this->createSwap();
        CallSignal::factory()->between($swapRequest, $sender)->offer('private-call')->create();

        $this->actingAs($outsider)
            ->getJson($this->signalUrl($swapRequest, ['in_call' => 1]))
            ->assertForbidden()
            ->assertDontSee('private-call');
        $this->actingAs($outsider)
            ->postJson($this->signalUrl($swapRequest), ['type' => 'join'])
            ->assertForbidden();

        $this->assertDatabaseCount('call_signals', 1);
        $this->actingAs($sender)
            ->getJson($this->signalUrl($swapRequest))
            ->assertJsonPath('peer_in_call', false);
    }

    public function test_pending_and_rejected_swaps_are_denied(): void
    {
        foreach ([SwapRequest::STATUS_PENDING, SwapRequest::STATUS_REJECTED, SwapRequest::STATUS_CANCELLED] as $swapStatus) {
            [$sender, , $swapRequest] = $this->createSwap(SkillSession::STATUS_CONFIRMED, $swapStatus);

            $this->actingAs($sender)->getJson($this->signalUrl($swapRequest))->assertForbidden();
            $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertForbidden();
        }

        $this->assertDatabaseCount('call_signals', 0);
    }

    public function test_calls_require_a_confirmed_session_and_close_after_completion(): void
    {
        foreach ([null, SkillSession::STATUS_PROPOSED, SkillSession::STATUS_CANCELLED, SkillSession::STATUS_COMPLETED] as $sessionStatus) {
            [$sender, $recipient, $swapRequest] = $this->createSwap($sessionStatus);

            $this->actingAs($sender)->getJson($this->signalUrl($swapRequest))->assertForbidden();
            $this->actingAs($recipient)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertForbidden();
        }

        $this->assertDatabaseCount('call_signals', 0);
    }

    public function test_completing_the_swap_mid_call_revokes_signaling(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();
        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertCreated();

        $swapRequest->skillSession->update(['status' => SkillSession::STATUS_COMPLETED, 'completed_at' => now()]);

        $this->actingAs($sender)->getJson($this->signalUrl($swapRequest, ['in_call' => 1]))->assertForbidden();
        $this->actingAs($sender)
            ->postJson($this->signalUrl($swapRequest), ['type' => 'offer', 'payload' => ['call_id' => 'late', 'sdp' => self::SDP]])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    #[DataProvider('invalidSignalProvider')]
    public function test_signal_payloads_are_validated(array $body, string $errorKey): void
    {
        [$sender, , $swapRequest] = $this->createSwap();

        $this->actingAs($sender)
            ->postJson($this->signalUrl($swapRequest), $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errorKey);

        $this->assertDatabaseCount('call_signals', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidSignalProvider(): array
    {
        $sdp = self::SDP;

        return [
            'missing type' => [[], 'type'],
            'unknown type' => [['type' => 'ring'], 'type'],
            'join with payload' => [['type' => 'join', 'payload' => ['call_id' => 'x']], 'payload'],
            'offer without payload' => [['type' => 'offer'], 'payload'],
            'offer without sdp' => [['type' => 'offer', 'payload' => ['call_id' => 'call-1']], 'payload.sdp'],
            'offer with non-sdp text' => [['type' => 'offer', 'payload' => ['call_id' => 'call-1', 'sdp' => '<script>alert(1)</script>']], 'payload.sdp'],
            'oversized sdp' => [['type' => 'answer', 'payload' => ['call_id' => 'call-1', 'sdp' => 'v=0'.str_repeat('a', CallSignal::MAX_SDP_LENGTH)]], 'payload.sdp'],
            'answer without call id' => [['type' => 'answer', 'payload' => ['sdp' => $sdp]], 'payload.call_id'],
            'unsafe call id' => [['type' => 'offer', 'payload' => ['call_id' => '../<b>', 'sdp' => $sdp]], 'payload.call_id'],
            'unexpected payload key' => [['type' => 'offer', 'payload' => ['call_id' => 'call-1', 'sdp' => $sdp, 'room' => 99]], 'payload'],
            'candidate without candidate' => [['type' => 'candidate', 'payload' => ['call_id' => 'call-1']], 'payload.candidate'],
            'candidate with unexpected key' => [['type' => 'candidate', 'payload' => ['call_id' => 'call-1', 'candidate' => ['candidate' => 'c', 'swap_request_id' => 2]]], 'payload.candidate'],
            'candidate with invalid line index' => [['type' => 'candidate', 'payload' => ['call_id' => 'call-1', 'candidate' => ['candidate' => 'c', 'sdpMLineIndex' => 'first']]], 'payload.candidate.sdpMLineIndex'],
            'media without video flag' => [['type' => 'media', 'payload' => ['audio' => true]], 'payload.video'],
            'media with non-boolean audio' => [['type' => 'media', 'payload' => ['audio' => 'loud', 'video' => false]], 'payload.audio'],
        ];
    }

    public function test_polling_parameters_are_validated(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();

        $this->actingAs($sender)
            ->getJson($this->signalUrl($swapRequest, ['after' => 'latest', 'in_call' => 'maybe']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['after', 'in_call']);
    }

    public function test_stored_payload_keeps_exact_sdp_and_only_relevant_fields(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();

        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), [
            'type' => 'candidate',
            'payload' => ['call_id' => 'call-1', 'candidate' => ['candidate' => 'candidate:1 1 udp 1 10.0.0.1 1 typ host', 'sdpMid' => '0']],
        ])->assertCreated();
        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), [
            'type' => 'offer',
            'payload' => ['call_id' => 'call-1', 'sdp' => self::SDP],
        ])->assertCreated();

        $signals = CallSignal::query()->orderBy('id')->get();

        $this->assertSame(['call_id' => 'call-1', 'candidate' => ['candidate' => 'candidate:1 1 udp 1 10.0.0.1 1 typ host', 'sdpMid' => '0']], $signals[0]->payload);
        $this->assertSame(self::SDP, $signals[1]->payload['sdp']);
    }

    public function test_leaving_or_going_silent_ends_presence(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwap();

        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertCreated();
        $this->actingAs($recipient)->getJson($this->signalUrl($swapRequest))->assertJsonPath('peer_in_call', true);

        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'leave'])->assertCreated();
        $this->actingAs($recipient)->getJson($this->signalUrl($swapRequest))->assertJsonPath('peer_in_call', false);

        $this->actingAs($sender)->getJson($this->signalUrl($swapRequest, ['in_call' => 1]))->assertOk();
        $this->actingAs($recipient)->getJson($this->signalUrl($swapRequest))->assertJsonPath('peer_in_call', true);

        $this->travel(config('webrtc.presence_ttl_seconds') + 1)->seconds();
        $this->actingAs($recipient)->getJson($this->signalUrl($swapRequest))->assertJsonPath('peer_in_call', false);
    }

    public function test_polling_without_in_call_does_not_mark_presence(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwap();

        $this->actingAs($sender)->getJson($this->signalUrl($swapRequest, ['in_call' => 0]))->assertOk();

        $this->actingAs($recipient)->getJson($this->signalUrl($swapRequest))->assertJsonPath('peer_in_call', false);
    }

    public function test_joining_discards_stale_signals_and_old_signals_are_prunable(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        [$sender, $recipient, $swapRequest] = $this->createSwap();
        $stale = CallSignal::factory()->between($swapRequest, $recipient)->offer()->create(['created_at' => now()->subHours(2)]);
        $recent = CallSignal::factory()->between($swapRequest, $recipient)->offer()->create(['created_at' => now()->subMinutes(5)]);

        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertCreated();

        $this->assertModelMissing($stale);
        $this->assertModelExists($recent);

        [, $otherRecipient, $otherSwapRequest] = $this->createSwap();
        $abandoned = CallSignal::factory()->between($otherSwapRequest, $otherRecipient)->create(['created_at' => now()->subHours(3)]);

        $this->artisan('model:prune', ['--model' => [CallSignal::class]])->assertSuccessful();

        $this->assertModelMissing($abandoned);
        $this->assertModelExists($recent);
    }

    public function test_call_activity_never_completes_the_session_or_awards_credits(): void
    {
        [$sender, $recipient, $swapRequest] = $this->createSwap();

        foreach ([$sender, $recipient] as $participant) {
            $this->actingAs($participant)->postJson($this->signalUrl($swapRequest), ['type' => 'join'])->assertCreated();
        }
        $this->actingAs($sender)->postJson($this->signalUrl($swapRequest), ['type' => 'offer', 'payload' => ['call_id' => 'c', 'sdp' => self::SDP]])->assertCreated();
        foreach ([$sender, $recipient] as $participant) {
            $this->actingAs($participant)->postJson($this->signalUrl($swapRequest), ['type' => 'leave'])->assertCreated();
        }

        $session = $swapRequest->skillSession->fresh();
        $this->assertSame(SkillSession::STATUS_CONFIRMED, $session->status);
        $this->assertNull($session->sender_confirmed_at);
        $this->assertNull($session->recipient_confirmed_at);
        $this->assertNull($session->completed_at);
        $this->assertSame(1, $sender->fresh()->skill_credits);
        $this->assertSame(1, $recipient->fresh()->skill_credits);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertDatabaseCount('swap_messages', 0);
    }

    public function test_workspace_renders_the_call_panel_with_roles_for_a_confirmed_session(): void
    {
        config(['webrtc.ice_servers' => [
            ['urls' => ['stun:stun.example.test:3478']],
            ['urls' => ['turn:turn.example.test:3478'], 'username' => 'swap-user', 'credential' => 'swap-secret'],
        ]]);
        [$sender, $recipient, $swapRequest] = $this->createSwap();
        $signal = CallSignal::factory()->between($swapRequest, $recipient)->create();

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-call-panel', false)
            ->assertSee('data-signal-url="'.route('swap-requests.call.signals.store', $swapRequest).'"', false)
            ->assertSee('data-signal-cursor="'.$signal->id.'"', false)
            ->assertSee('data-call-role="offerer"', false)
            ->assertSee('data-peer-name="Justine"', false)
            ->assertSee('stun:stun.example.test:3478')
            ->assertSee('turn:turn.example.test:3478')
            ->assertSee('Join Call')
            ->assertSeeInOrder(['data-workspace-panel="call"', 'data-workspace-panel="chat"', 'data-workspace-panel="session"'], false)
            ->assertSee('data-workspace-tab="call"', false)
            ->assertSee('Send Message')
            ->assertDontSee('Confirm Session Completed', false)
            ->assertSee('Completion available after the session ends.');

        $this->actingAs($recipient)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('data-call-role="answerer"', false)
            ->assertSee('data-peer-name="Jiro"', false);
    }

    public function test_call_panel_is_locked_before_confirmation_and_closed_after_completion(): void
    {
        foreach ([null, SkillSession::STATUS_PROPOSED] as $sessionStatus) {
            [$sender, , $swapRequest] = $this->createSwap($sessionStatus);

            $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
                ->assertOk()
                ->assertSee('Calls unlock once the session is confirmed.')
                ->assertDontSee('data-signal-url', false)
                ->assertDontSee('Join Call');
        }

        [$sender, , $swapRequest] = $this->createSwap(SkillSession::STATUS_COMPLETED);

        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertOk()
            ->assertSee('Calls are closed.')
            ->assertDontSee('data-signal-url', false)
            ->assertDontSee('Join Call')
            ->assertDontSee('stun:', false);
    }

    public function test_unrelated_users_cannot_see_call_configuration(): void
    {
        [, , $swapRequest] = $this->createSwap();
        [$outsider] = $this->createSwap(null);

        $this->actingAs($outsider)->get(route('swap-requests.chat', $swapRequest))
            ->assertForbidden()
            ->assertDontSee('data-signal-url', false);
    }

    public function test_chat_messages_can_be_sent_in_the_background_during_a_call(): void
    {
        [$sender, , $swapRequest] = $this->createSwap();

        $this->actingAs($sender)
            ->postJson(route('swap-requests.messages.store', $swapRequest), ['message' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->actingAs($sender)
            ->postJson(route('swap-requests.messages.store', $swapRequest), ['message' => 'Can you hear me?'])
            ->assertRedirect(route('swap-requests.chat', $swapRequest));

        $this->assertDatabaseHas('swap_messages', ['sender_id' => $sender->id, 'message' => 'Can you hear me?']);
        $this->assertDatabaseCount('call_signals', 0);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function signalUrl(SwapRequest $swapRequest, array $query = []): string
    {
        return route('swap-requests.call.signals.index', ['swapRequest' => $swapRequest, ...$query]);
    }

    /** @return array{User, User, SwapRequest} */
    private function createSwap(
        ?string $sessionStatus = SkillSession::STATUS_CONFIRMED,
        string $swapStatus = SwapRequest::STATUS_ACCEPTED
    ): array {
        $sender = User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient = User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::firstOrCreate(['name' => 'TypeScript'], ['is_approved' => true]);
        $requestedSkill = Skill::firstOrCreate(['name' => 'PHP'], ['is_approved' => true]);

        $swapRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => $swapStatus,
            'responded_at' => now(),
        ]);

        if ($sessionStatus !== null) {
            $isConfirmedOrLater = in_array($sessionStatus, [SkillSession::STATUS_CONFIRMED, SkillSession::STATUS_COMPLETED], true);

            SkillSession::create([
                'swap_request_id' => $swapRequest->id,
                'scheduled_by' => $sender->id,
                'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
                'scheduled_at' => $sessionStatus === SkillSession::STATUS_COMPLETED ? now()->subDay() : now()->subMinutes(5),
                'duration_minutes' => 60,
                'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
                'status' => $sessionStatus,
                'confirmed_at' => $isConfirmedOrLater ? now()->subDay() : null,
                'completed_at' => $sessionStatus === SkillSession::STATUS_COMPLETED ? now() : null,
            ]);
        }

        return [$sender, $recipient, $swapRequest];
    }
}
