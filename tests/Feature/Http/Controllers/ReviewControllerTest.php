<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Review;
use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sender_and_recipient_can_open_review_form_with_correct_perspective(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();

        $this->actingAs($sender)->get(route('reviews.create', $session))
            ->assertOk()->assertSee('How was your session with')
            ->assertSee('Justine')->assertSee('You taught')->assertSee('TypeScript')
            ->assertSee('You learned')->assertSee('PHP');

        $this->actingAs($recipient)->get(route('reviews.create', $session))
            ->assertOk()->assertSee('Jiro')->assertSee('You taught')->assertSee('PHP')
            ->assertSee('You learned')->assertSee('TypeScript');
    }

    public function test_sender_review_derives_recipient_and_ignores_manipulated_reviewee(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();

        $this->actingAs($sender)->post(route('reviews.store', $session), [
            'rating' => 5,
            'comment' => '  Very patient teacher.  ',
            'reviewee_id' => $sender->id,
        ])->assertRedirect(route('swap-requests.chat', $session->swap_request_id))
            ->assertSessionHas('success', 'Review submitted!');

        $this->assertDatabaseHas('reviews', [
            'skill_session_id' => $session->id,
            'reviewer_id' => $sender->id,
            'reviewee_id' => $recipient->id,
            'rating' => 5,
            'comment' => 'Very patient teacher.',
        ]);
        $this->assertDatabaseMissing('reviews', ['reviewee_id' => $sender->id]);
    }

    public function test_recipient_review_is_written_about_sender(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();

        $this->actingAs($recipient)->post(route('reviews.store', $session), [
            'rating' => 4,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'skill_session_id' => $session->id,
            'reviewer_id' => $recipient->id,
            'reviewee_id' => $sender->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    public function test_rating_boundaries_one_and_five_are_valid(): void
    {
        foreach ([1, 5] as $rating) {
            [$sender, , , $session] = $this->createCompletedSession();

            $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => $rating])
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_out_of_range_and_non_integer_ratings_are_rejected(): void
    {
        foreach ([0, 6, 4.5, 'excellent'] as $rating) {
            [$sender, , , $session] = $this->createCompletedSession();

            $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => $rating])
                ->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_is_required_and_comment_may_not_exceed_one_thousand_characters(): void
    {
        [$sender, , , $session] = $this->createCompletedSession();

        $this->actingAs($sender)->post(route('reviews.store', $session), [
            'comment' => str_repeat('a', 1001),
        ])->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_unrelated_admin_and_incomplete_participant_cannot_review(): void
    {
        [$sender, , , $session] = $this->createCompletedSession();

        $this->get(route('reviews.create', $session))->assertRedirect(route('login'));
        $this->post(route('reviews.store', $session), ['rating' => 5])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->onboarded()->create())
            ->post(route('reviews.store', $session), ['rating' => 5])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reviews.create', $session))->assertRedirect(route('admin.dashboard'));

        $sender->update(['onboarding_completed' => false]);
        $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => 5])
            ->assertRedirect(route('onboarding.welcome'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_non_completed_sessions_cannot_be_reviewed(): void
    {
        foreach ([SkillSession::STATUS_PROPOSED, SkillSession::STATUS_CONFIRMED, SkillSession::STATUS_CANCELLED] as $status) {
            [$sender, , , $session] = $this->createCompletedSession(['status' => $status]);

            $this->actingAs($sender)->get(route('reviews.create', $session))->assertForbidden();
            $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => 5])->assertForbidden();
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_repeated_submission_returns_friendly_message_without_changing_review(): void
    {
        [$sender, , , $session] = $this->createCompletedSession();
        $this->actingAs($sender)->post(route('reviews.store', $session), [
            'rating' => 5,
            'comment' => 'Original review',
        ]);

        $this->actingAs($sender)->post(route('reviews.store', $session), [
            'rating' => 1,
            'comment' => 'Replacement attempt',
        ])->assertSessionHas('info', 'You have already reviewed this skill swap.');
        $this->actingAs($sender)->get(route('reviews.create', $session))
            ->assertSessionHas('info', 'You have already reviewed this skill swap.');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['rating' => 5, 'comment' => 'Original review']);
        $this->assertDatabaseMissing('reviews', ['comment' => 'Replacement attempt']);
    }

    public function test_database_unique_constraint_prevents_duplicate_reviewer_for_session(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();
        Review::create([
            'skill_session_id' => $session->id,
            'reviewer_id' => $sender->id,
            'reviewee_id' => $recipient->id,
            'rating' => 5,
        ]);

        $this->expectException(QueryException::class);

        Review::create([
            'skill_session_id' => $session->id,
            'reviewer_id' => $sender->id,
            'reviewee_id' => $recipient->id,
            'rating' => 4,
        ]);
    }

    public function test_both_participants_can_submit_one_separate_review(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();

        $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => 5]);
        $this->actingAs($recipient)->post(route('reviews.store', $session), ['rating' => 4]);

        $this->assertDatabaseCount('reviews', 2);
        $this->assertSame(1, $sender->reviewsWritten()->count());
        $this->assertSame(1, $recipient->reviewsWritten()->count());
        $this->assertSame(1, $sender->reviewsReceived()->count());
        $this->assertSame(1, $recipient->reviewsReceived()->count());
    }

    public function test_discover_calculates_average_and_count_and_handles_zero_reviews(): void
    {
        $viewer = User::factory()->onboarded()->create();
        $reviewee = User::factory()->onboarded()->create(['name' => 'Justine']);
        $newUser = User::factory()->onboarded()->create(['name' => 'New User']);

        foreach ([5, 5, 4] as $rating) {
            [$reviewer, , , $session] = $this->createCompletedSession([], null, $reviewee);
            Review::create([
                'skill_session_id' => $session->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $reviewee->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($viewer)->get(route('discover.index'));

        $justine = $response->viewData('users')->firstWhere('id', $reviewee->id);
        $this->assertSame(3, $justine->reviews_received_count);
        $this->assertSame(4.666666666666667, (float) $justine->reviews_received_avg_rating);
        $response->assertSee('★ 4.7')->assertSee('(3 reviews)')
            ->assertSee($newUser->name)->assertSee('No reviews yet');
    }

    public function test_match_page_shows_rating_recent_review_context_and_escaped_comment(): void
    {
        $viewer = User::factory()->onboarded()->create();
        $reviewee = User::factory()->onboarded()->create(['name' => 'Justine']);
        [$reviewer, , , $session] = $this->createCompletedSession([], null, $reviewee);
        Review::create([
            'skill_session_id' => $session->id,
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $reviewee->id,
            'rating' => 5,
            'comment' => '<script>alert("review")</script> Patient teacher.',
        ]);

        $this->actingAs($viewer)->get(route('matches.show', $reviewee))
            ->assertSee('★ 5.0')->assertSee('1 completed review')
            ->assertSee('Recent Reviews')->assertSee($reviewer->name)
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
    }

    public function test_dashboard_and_closed_chat_show_review_availability_then_submitted_state(): void
    {
        [$sender, , $swapRequest, $session] = $this->createCompletedSession();

        $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('Leave Review')->assertSee(route('reviews.create', $session), false);
        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Leave Review')->assertDontSee('Send Message');

        $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => 5]);

        $this->actingAs($sender)->get(route('dashboard'))
            ->assertSee('Review Submitted')->assertSee('★★★★★')->assertDontSee(route('reviews.create', $session), false);
        $this->actingAs($sender)->get(route('swap-requests.chat', $swapRequest))
            ->assertSee('Review Submitted')->assertSee('★★★★★')->assertDontSee('Leave Review')
            ->assertSee('This conversation is closed.')->assertDontSee('Send Message');
    }

    public function test_review_does_not_change_credits_or_completed_session_state(): void
    {
        [$sender, $recipient, , $session] = $this->createCompletedSession();
        $sender->update(['skill_credits' => 3]);
        $recipient->update(['skill_credits' => 4]);
        $completedAt = $session->completed_at->toDateTimeString();

        $this->actingAs($sender)->post(route('reviews.store', $session), ['rating' => 5]);

        $this->assertSame(3, $sender->fresh()->skill_credits);
        $this->assertSame(4, $recipient->fresh()->skill_credits);
        $this->assertSame(SkillSession::STATUS_COMPLETED, $session->fresh()->status);
        $this->assertSame($completedAt, $session->fresh()->completed_at->toDateTimeString());
    }

    /** @return array{User, User, SwapRequest, SkillSession} */
    private function createCompletedSession(
        array $sessionAttributes = [],
        ?User $sender = null,
        ?User $recipient = null
    ): array {
        $sender ??= User::factory()->onboarded()->create(['name' => 'Jiro']);
        $recipient ??= User::factory()->onboarded()->create(['name' => 'Justine']);
        $offeredSkill = Skill::firstOrCreate(['name' => 'TypeScript'], ['is_approved' => true]);
        $requestedSkill = Skill::firstOrCreate(['name' => 'PHP'], ['is_approved' => true]);
        $swapRequest = SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
        $session = SkillSession::create([
            'swap_request_id' => $swapRequest->id,
            'scheduled_by' => $sender->id,
            'scheduled_at' => '2026-09-09 07:00:00',
            'duration_minutes' => 60,
            'meeting_type' => SkillSession::MEETING_TYPE_ONLINE,
            'status' => SkillSession::STATUS_COMPLETED,
            'confirmed_at' => '2026-09-08 08:00:00',
            'sender_confirmed_at' => '2026-09-09 08:00:00',
            'recipient_confirmed_at' => '2026-09-09 08:01:00',
            'completed_at' => '2026-09-09 08:01:00',
            ...$sessionAttributes,
        ]);

        return [$sender, $recipient, $swapRequest, $session];
    }
}
