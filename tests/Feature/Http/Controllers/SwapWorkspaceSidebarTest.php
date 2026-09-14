<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SwapWorkspaceSidebarTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_swaps_appear_for_sender_and_recipient_from_their_own_perspective(): void
    {
        $jiro = $this->createUser('Jiro');
        $justine = $this->createUser('Justine');
        $maria = $this->createUser('Maria');
        $withJustine = $this->createSwap($jiro, $justine, ['TypeScript', 'PHP']);
        $withMaria = $this->createSwap($maria, $jiro, ['Laravel', 'Figma']);

        $this->actingAs($jiro)->get(route('swap-requests.chat', $withJustine))
            ->assertOk()
            ->assertSeeInOrder(['data-swap-group="active"', 'data-swap-id="'.$withJustine->id.'"', 'Justine', 'TypeScript ↔ PHP'], false)
            ->assertSeeInOrder(['data-swap-id="'.$withMaria->id.'"', 'Maria', 'Figma ↔ Laravel'], false)
            ->assertSee('href="'.route('swap-requests.chat', $withMaria).'"', false);

        $this->actingAs($justine)->get(route('swap-requests.chat', $withJustine))
            ->assertOk()
            ->assertSeeInOrder(['data-swap-id="'.$withJustine->id.'"', 'Jiro', 'PHP ↔ TypeScript'], false)
            ->assertDontSee('data-swap-id="'.$withMaria->id.'"', false)
            ->assertDontSee('Maria');
    }

    public function test_unrelated_swaps_and_their_messages_never_appear(): void
    {
        $jiro = $this->createUser('Jiro');
        $swap = $this->createSwap($jiro, $this->createUser('Justine'));
        $outsiderSwap = $this->createSwap($this->createUser('Olivia'), $this->createUser('Oscar'), ['Rust', 'Go']);
        $outsiderPending = $this->createSwap($this->createUser('Petra'), $this->createUser('Quinn'), ['Java', 'Kotlin'], SwapRequest::STATUS_PENDING);
        SwapMessage::create(['swap_request_id' => $outsiderSwap->id, 'sender_id' => $outsiderSwap->sender_id, 'message' => 'Outsider secret']);

        $this->actingAs($jiro)->get(route('swap-requests.chat', $swap))
            ->assertOk()
            ->assertDontSee('data-swap-id="'.$outsiderSwap->id.'"', false)
            ->assertDontSee('data-pending-swap-id="'.$outsiderPending->id.'"', false)
            ->assertDontSee('Olivia')->assertDontSee('Oscar')->assertDontSee('Petra')->assertDontSee('Quinn')
            ->assertDontSee('Rust ↔ Go')
            ->assertDontSee('Outsider secret');
    }

    public function test_only_the_opened_swap_is_marked_as_selected(): void
    {
        $jiro = $this->createUser('Jiro');
        $opened = $this->createSwap($jiro, $this->createUser('Justine'));
        $other = $this->createSwap($jiro, $this->createUser('Maria'));

        $response = $this->actingAs($jiro)->get(route('swap-requests.chat', $opened))
            ->assertOk()
            ->assertSee('data-swap-id="'.$opened->id.'" aria-current="page"', false)
            ->assertDontSee('data-swap-id="'.$other->id.'" aria-current="page"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
    }

    public function test_active_items_show_status_derived_from_existing_session_state(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $jiro = $this->createUser('Jiro');
        $opened = $this->createSwap($jiro, $this->createUser('Justine'));
        $needsResponse = $this->createSwap($this->createUser('Maria'), $jiro, ['Laravel', 'Figma'], sessionStatus: SkillSession::STATUS_PROPOSED);
        $awaiting = $this->createSwap($jiro, $this->createUser('Alex'), ['C++', 'C'], sessionStatus: SkillSession::STATUS_CONFIRMED, sessionAttributes: ['scheduled_at' => now()->subHour()]);

        $this->actingAs($jiro)->get(route('swap-requests.chat', $opened))
            ->assertOk()
            ->assertSeeInOrder(['data-swap-id="'.$opened->id.'"', 'Discussing Schedule'], false)
            ->assertSeeInOrder(['data-swap-id="'.$needsResponse->id.'"', 'Needs Your Response'], false)
            ->assertSeeInOrder(['data-swap-id="'.$awaiting->id.'"', 'C++ ↔ C', 'Awaiting Completion'], false);
    }

    public function test_completed_swaps_appear_only_in_history_and_open_read_only(): void
    {
        $jiro = $this->createUser('Jiro');
        $active = $this->createSwap($jiro, $this->createUser('Justine'));
        $completed = $this->createSwap($jiro, $this->createUser('John'), ['PHP', 'Photoshop'], sessionStatus: SkillSession::STATUS_COMPLETED);

        $content = $this->actingAs($jiro)->get(route('swap-requests.chat', $active))->assertOk()->getContent();

        $historyPosition = strpos($content, 'data-swap-group="history"');
        $this->assertNotFalse($historyPosition);
        $this->assertGreaterThan($historyPosition, strpos($content, 'data-swap-id="'.$completed->id.'"'));
        $this->assertLessThan($historyPosition, strpos($content, 'data-swap-id="'.$active->id.'"'));
        $this->assertSame(1, substr_count($content, 'data-swap-id="'.$completed->id.'"'));

        $this->actingAs($jiro)->get(route('swap-requests.chat', $completed))
            ->assertOk()
            ->assertSee('data-swap-id="'.$completed->id.'" aria-current="page"', false)
            ->assertSee('This conversation is closed.')
            ->assertDontSee('Send Message')
            ->assertDontSee('Join Call');
    }

    public function test_pending_requests_are_listed_separately_without_chat_or_call_access(): void
    {
        $jiro = $this->createUser('Jiro');
        $active = $this->createSwap($jiro, $this->createUser('Justine'));
        $received = $this->createSwap($this->createUser('Alex'), $jiro, ['Python', 'SQL'], SwapRequest::STATUS_PENDING);
        $sent = $this->createSwap($jiro, $this->createUser('John'), ['PHP', 'Photoshop'], SwapRequest::STATUS_PENDING);

        $this->actingAs($jiro)->get(route('swap-requests.chat', $active))
            ->assertOk()
            ->assertSeeInOrder(['data-swap-group="pending"', 'Alex', 'SQL ↔ Python', 'Request received'], false)
            ->assertSee('href="'.route('dashboard').'#incoming-requests" class="swap-item is-pending" data-pending-swap-id="'.$received->id.'"', false)
            ->assertSee('href="'.route('dashboard').'#sent-requests" class="swap-item is-pending" data-pending-swap-id="'.$sent->id.'"', false)
            ->assertSee('Request sent')
            ->assertDontSee('data-swap-id="'.$received->id.'"', false)
            ->assertDontSee(route('swap-requests.chat', $received), false)
            ->assertDontSee(route('swap-requests.chat', $sent), false);

        foreach ([$received, $sent] as $pendingSwap) {
            $this->actingAs($jiro)->get(route('swap-requests.chat', $pendingSwap))->assertForbidden();
            $this->actingAs($jiro)->getJson(route('swap-requests.call.signals.index', $pendingSwap))->assertForbidden();
        }

        $this->actingAs($jiro)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="incoming-requests"', false)
            ->assertSee('id="sent-requests"', false);
    }

    public function test_user_without_active_swaps_gets_the_discover_action(): void
    {
        $jiro = $this->createUser('Jiro');
        $completed = $this->createSwap($jiro, $this->createUser('John'), sessionStatus: SkillSession::STATUS_COMPLETED);

        $this->actingAs($jiro)->get(route('swap-requests.chat', $completed))
            ->assertOk()
            ->assertSee('No active swaps yet.')
            ->assertSee('Find someone to exchange skills with.')
            ->assertSee('href="'.route('discover.index').'"', false)
            ->assertSee('Discover People');

        $active = $this->createSwap($jiro, $this->createUser('Justine'));

        $this->actingAs($jiro)->get(route('swap-requests.chat', $active))
            ->assertOk()
            ->assertDontSee('No active swaps yet.')
            ->assertDontSee('Discover People');
    }

    public function test_only_the_selected_conversation_loads_its_messages(): void
    {
        $jiro = $this->createUser('Jiro');
        $opened = $this->createSwap($jiro, $this->createUser('Justine'));
        $other = $this->createSwap($jiro, $this->createUser('Maria'));
        SwapMessage::create(['swap_request_id' => $opened->id, 'sender_id' => $jiro->id, 'message' => 'Opened conversation message']);
        SwapMessage::create(['swap_request_id' => $other->id, 'sender_id' => $jiro->id, 'message' => 'Message from the other swap']);

        $messageLoads = [];
        DB::listen(function ($query) use (&$messageLoads): void {
            if (str_starts_with($query->sql, 'select * from "swap_messages"')) {
                $messageLoads[] = $query;
            }
        });

        $this->actingAs($jiro)->get(route('swap-requests.chat', $opened))
            ->assertOk()
            ->assertSee('Opened conversation message')
            ->assertDontSee('Message from the other swap');

        $this->assertCount(1, $messageLoads);
        $this->assertStringContainsString('"swap_request_id" in ('.$opened->id.')', $messageLoads[0]->sql);
    }

    public function test_sidebar_query_count_does_not_grow_with_the_number_of_swaps(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $jiro = $this->createUser('Jiro');
        $opened = $this->createSwap($jiro, $this->createUser('Justine'));
        // Baseline covers every sidebar group, so each eager load already runs once.
        $this->createSwap($jiro, $this->createUser('Maria'), sessionStatus: SkillSession::STATUS_CONFIRMED);
        $this->createSwap($jiro, $this->createUser('Hana'), sessionStatus: SkillSession::STATUS_COMPLETED);
        $this->createSwap($this->createUser('Alex'), $jiro, status: SwapRequest::STATUS_PENDING);
        $this->createSwap($jiro, $this->createUser('Zed'), status: SwapRequest::STATUS_PENDING);

        $baseline = $this->recordQueries(fn (): TestResponse => $this->actingAs($jiro)->get(route('swap-requests.chat', $opened))->assertOk());

        foreach (['Ana', 'Ben', 'Cara'] as $name) {
            $swap = $this->createSwap($jiro, $this->createUser($name), sessionStatus: SkillSession::STATUS_PROPOSED);
            SwapMessage::create(['swap_request_id' => $swap->id, 'sender_id' => $jiro->id, 'message' => "Hello {$name}"]);
        }
        $this->createSwap($jiro, $this->createUser('Dan'), sessionStatus: SkillSession::STATUS_COMPLETED);
        $this->createSwap($jiro, $this->createUser('Eve'), status: SwapRequest::STATUS_PENDING);

        $withMoreSwaps = $this->recordQueries(fn (): TestResponse => $this->actingAs($jiro)->get(route('swap-requests.chat', $opened))->assertOk()->assertSee('Cara'));

        $this->assertSame($this->queryShapes($baseline), $this->queryShapes($withMoreSwaps));
    }

    /**
     * Sorted query statements with eager-load id lists normalized, so only the query shapes are compared.
     *
     * @param  list<string>  $queries
     * @return list<string>
     */
    private function queryShapes(array $queries): array
    {
        $shapes = array_map(fn (string $sql): string => preg_replace('/ in \([^)]*\)/', ' in (…)', $sql), $queries);
        sort($shapes);

        return $shapes;
    }

    public function test_existing_chat_and_call_authorization_is_unchanged(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $jiro = $this->createUser('Jiro');
        $swap = $this->createSwap($jiro, $this->createUser('Justine'), sessionStatus: SkillSession::STATUS_CONFIRMED);
        $outsider = $this->createUser('Olivia');
        $this->createSwap($outsider, $this->createUser('Oscar'));

        $this->get(route('swap-requests.chat', $swap))->assertRedirect(route('login'));
        $this->actingAs($outsider)->get(route('swap-requests.chat', $swap))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('swap-requests.messages.index', $swap))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('swap-requests.call.signals.index', $swap))->assertForbidden();
        $this->actingAs($outsider)->postJson(route('swap-requests.call.signals.store', $swap), ['type' => 'join'])->assertForbidden();

        $this->actingAs($jiro)->get(route('swap-requests.chat', $swap))->assertOk()->assertSee('data-call-panel', false);
        $this->actingAs($jiro)->getJson(route('swap-requests.call.signals.index', $swap))->assertOk();
    }

    /**
     * @return list<string>
     */
    private function recordQueries(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return $queries;
    }

    private function createUser(string $name): User
    {
        return User::factory()->onboarded()->create(['name' => $name]);
    }

    /**
     * @param  array{string, string}  $skillNames  [offered, requested]
     * @param  array<string, mixed>  $sessionAttributes
     */
    private function createSwap(
        User $sender,
        User $recipient,
        array $skillNames = ['TypeScript', 'PHP'],
        string $status = SwapRequest::STATUS_ACCEPTED,
        ?string $sessionStatus = null,
        array $sessionAttributes = [],
    ): SwapRequest {
        $swapRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => Skill::firstOrCreate(['name' => $skillNames[0]], ['is_approved' => true])->id,
            'requested_skill_id' => Skill::firstOrCreate(['name' => $skillNames[1]], ['is_approved' => true])->id,
            'status' => $status,
            'responded_at' => $status === SwapRequest::STATUS_PENDING ? null : now(),
        ]);

        if ($sessionStatus !== null) {
            SkillSession::create([
                'swap_request_id' => $swapRequest->id,
                'scheduled_by' => $sender->id,
                'teaching_side' => SkillSession::TEACHING_SIDE_SENDER,
                'scheduled_at' => $sessionStatus === SkillSession::STATUS_COMPLETED ? now()->subDay() : now()->addDay(),
                'duration_minutes' => 60,
                'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
                'status' => $sessionStatus,
                'confirmed_at' => in_array($sessionStatus, [SkillSession::STATUS_CONFIRMED, SkillSession::STATUS_COMPLETED], true) ? now() : null,
                'completed_at' => $sessionStatus === SkillSession::STATUS_COMPLETED ? now() : null,
                ...$sessionAttributes,
            ]);
        }

        return $swapRequest;
    }
}
