<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SwapRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_compatible_user_can_send_a_pending_swap_request(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();

        $response = $this->actingAs($sender)->post(route('swap-requests.store'), [
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'message' => 'Would you like to swap skills?',
        ]);

        $response
            ->assertRedirect(route('matches.show', $recipient))
            ->assertSessionHas('success', 'Swap request sent successfully!');

        $this->assertDatabaseHas('swap_requests', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'message' => 'Would you like to swap skills?',
            'status' => SwapRequest::STATUS_PENDING,
        ]);
    }

    public function test_user_cannot_send_a_swap_request_to_themselves(): void
    {
        $user = User::factory()->onboarded()->create();
        $offeredSkill = $this->createSkill('Photography');
        $requestedSkill = $this->createSkill('Cooking');
        $user->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $user->learningSkills()->attach($requestedSkill, ['type' => 'learn']);

        $this->actingAs($user)
            ->post(route('swap-requests.store'), $this->requestPayload($user, $offeredSkill, $requestedSkill))
            ->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_user_cannot_offer_a_skill_they_do_not_teach(): void
    {
        [$sender, $recipient, , $requestedSkill] = $this->createCompatibleUsers();
        $unownedSkill = $this->createSkill('French');
        $recipient->learningSkills()->attach($unownedSkill, ['type' => 'learn']);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $unownedSkill, $requestedSkill))
            ->assertSessionHasErrors('offered_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_user_cannot_offer_a_skill_the_recipient_does_not_want(): void
    {
        [$sender, $recipient, , $requestedSkill] = $this->createCompatibleUsers();
        $otherSkill = $this->createSkill('Woodworking');
        $sender->teachingSkills()->attach($otherSkill, ['type' => 'teach']);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $otherSkill, $requestedSkill))
            ->assertSessionHasErrors('offered_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_user_cannot_request_a_skill_they_do_not_want_to_learn(): void
    {
        [$sender, $recipient, $offeredSkill] = $this->createCompatibleUsers();
        $unwantedSkill = $this->createSkill('Public Speaking');
        $recipient->teachingSkills()->attach($unwantedSkill, ['type' => 'teach']);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $unwantedSkill))
            ->assertSessionHasErrors('requested_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_user_cannot_request_a_skill_the_recipient_does_not_teach(): void
    {
        [$sender, $recipient, $offeredSkill] = $this->createCompatibleUsers();
        $untaughtSkill = $this->createSkill('Illustration');
        $sender->learningSkills()->attach($untaughtSkill, ['type' => 'learn']);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $untaughtSkill))
            ->assertSessionHasErrors('requested_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_user_cannot_send_a_request_without_two_way_compatibility(): void
    {
        $sender = User::factory()->onboarded()->create();
        $recipient = User::factory()->onboarded()->create();
        $offeredSkill = $this->createSkill('PHP');
        $requestedSkill = $this->createSkill('Graphic Design');
        $sender->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $sender->learningSkills()->attach($requestedSkill, ['type' => 'learn']);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $requestedSkill))
            ->assertSessionHasErrors('offered_skill_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_duplicate_pending_swap_request_is_prevented(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();
        $payload = $this->requestPayload($recipient, $offeredSkill, $requestedSkill);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $payload)
            ->assertSessionHas('success');

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $payload)
            ->assertRedirect(route('matches.show', $recipient))
            ->assertSessionHas('info', 'You already have a pending swap request for this skill exchange.');

        $this->assertDatabaseCount('swap_requests', 1);
    }

    public function test_guest_cannot_create_a_swap_request(): void
    {
        [, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();

        $this->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $requestedSkill))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_admin_cannot_receive_a_swap_request(): void
    {
        $sender = User::factory()->onboarded()->create();
        $recipient = User::factory()->admin()->create();
        [$offeredSkill, $requestedSkill] = $this->attachCompatibleSkills($sender, $recipient);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $requestedSkill))
            ->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_incomplete_user_cannot_receive_a_swap_request(): void
    {
        $sender = User::factory()->onboarded()->create();
        $recipient = User::factory()->create(['onboarding_completed' => false]);
        [$offeredSkill, $requestedSkill] = $this->attachCompatibleSkills($sender, $recipient);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $requestedSkill))
            ->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_incomplete_sender_is_redirected_to_onboarding(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();
        $sender->update(['onboarding_completed' => false]);

        $this->actingAs($sender)
            ->post(route('swap-requests.store'), $this->requestPayload($recipient, $offeredSkill, $requestedSkill))
            ->assertRedirect(route('onboarding.welcome'));

        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_match_page_form_only_contains_compatible_skills(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();
        $incompatibleSkill = $this->createSkill('Unrelated Skill');
        $sender->teachingSkills()->attach($incompatibleSkill, ['type' => 'teach']);

        $this->actingAs($sender)
            ->get(route('matches.show', $recipient))
            ->assertOk()
            ->assertSee('action="'.route('swap-requests.store').'"', false)
            ->assertSee('name="offered_skill_id"', false)
            ->assertSee('name="requested_skill_id"', false)
            ->assertSee($offeredSkill->name)
            ->assertSee($requestedSkill->name)
            ->assertDontSee($incompatibleSkill->name);
    }

    public function test_pending_request_is_visible_to_sender_and_recipient(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();

        SwapRequest::create([
            ...$this->requestPayload($recipient, $offeredSkill, $requestedSkill),
            'sender_id' => $sender->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sent Swap Requests')
            ->assertSee($recipient->name)
            ->assertSee('Status: Pending');

        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Incoming Swap Requests')
            ->assertSee($sender->name)
            ->assertSee('Status: Pending');
    }

    public function test_match_page_button_posts_the_matched_skills_without_selects(): void
    {
        [$sender, $recipient, $offeredSkill, $requestedSkill] = $this->createCompatibleUsers();

        $this->actingAs($sender)
            ->get(route('matches.show', $recipient))
            ->assertOk()
            ->assertSee('Send Swap Request')
            ->assertSee('name="offered_skill_id"', false)
            ->assertSee('value="'.$offeredSkill->id.'"', false)
            ->assertSee('name="requested_skill_id"', false)
            ->assertSee('value="'.$requestedSkill->id.'"', false);

        // The button alone must still create a valid swap request.
        $this->actingAs($sender)
            ->post(route('swap-requests.store'), [
                'recipient_id' => $recipient->id,
                'offered_skill_id' => $offeredSkill->id,
                'requested_skill_id' => $requestedSkill->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('swap_requests', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @return array{User, User, Skill, Skill}
     */
    private function createCompatibleUsers(): array
    {
        $sender = User::factory()->onboarded()->create();
        $recipient = User::factory()->onboarded()->create();
        [$offeredSkill, $requestedSkill] = $this->attachCompatibleSkills($sender, $recipient);

        return [$sender, $recipient, $offeredSkill, $requestedSkill];
    }

    /**
     * @return array{Skill, Skill}
     */
    private function attachCompatibleSkills(User $sender, User $recipient): array
    {
        $offeredSkill = $this->createSkill('Web Development');
        $requestedSkill = $this->createSkill('Graphic Design');

        $sender->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $sender->learningSkills()->attach($requestedSkill, ['type' => 'learn']);
        $recipient->teachingSkills()->attach($requestedSkill, ['type' => 'teach']);
        $recipient->learningSkills()->attach($offeredSkill, ['type' => 'learn']);

        return [$offeredSkill, $requestedSkill];
    }

    private function createSkill(string $name): Skill
    {
        return Skill::create([
            'name' => $name,
            'is_approved' => true,
        ]);
    }

    /**
     * @return array{recipient_id: int, offered_skill_id: int, requested_skill_id: int}
     */
    private function requestPayload(User $recipient, Skill $offeredSkill, Skill $requestedSkill): array
    {
        return [
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
        ];
    }
}
