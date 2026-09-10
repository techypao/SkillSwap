<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Program;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AcademicProfileDisplayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_shows_academic_profile_and_skill_category(): void
    {
        [$currentUser, , $laravel, $figma] = $this->createCanonicalMatch();

        $this->actingAs($currentUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('BSIT')
            ->assertSee('4th Year')
            ->assertSee($figma->name)
            ->assertSee('Design &amp; Creative', false)
            ->assertSee($laravel->name)
            ->assertSee('Technology &amp; Programming', false)
            ->assertDontSee('Intermediate')
            ->assertDontSee('Beginner');
    }

    public function test_discover_shows_program_year_and_skills_without_proficiency(): void
    {
        [$currentUser, $otherUser] = $this->createCanonicalMatch();

        $this->actingAs($currentUser)
            ->get(route('discover.index'))
            ->assertOk()
            ->assertSee($otherUser->name)
            ->assertSee('BSCS')
            ->assertSee('3rd Year')
            ->assertSee('Laravel')
            ->assertSee('Figma')
            ->assertDontSee('Advanced')
            ->assertDontSee('Beginner');
    }

    public function test_compatibility_uses_exact_skill_ids_and_shows_richer_context(): void
    {
        [$currentUser, $otherUser] = $this->createCanonicalMatch();

        $this->actingAs($currentUser)
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('Mutual Match')
            ->assertSee('Technology &amp; Programming', false)
            ->assertSee('Design &amp; Creative', false)
            ->assertDontSee('proficiency');

        $sameLabelDifferentId = Skill::create(['name' => 'LARAVEL', 'is_approved' => true]);
        $otherUser->teachingSkills()->detach();
        $otherUser->teachingSkills()->attach($sameLabelDifferentId, [
            'type' => 'teach',
            'proficiency' => 'advanced',
        ]);

        $this->actingAs($currentUser)
            ->get(route('matches.show', $otherUser))
            ->assertOk()
            ->assertSee('Not a Mutual Match');
    }

    public function test_swap_request_still_uses_canonical_skill_ids(): void
    {
        [$currentUser, $otherUser, $laravel, $figma] = $this->createCanonicalMatch();

        $this->actingAs($currentUser)
            ->post(route('swap-requests.store'), [
                'recipient_id' => $otherUser->id,
                'offered_skill_id' => $figma->id,
                'requested_skill_id' => $laravel->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('swap_requests', [
            'sender_id' => $currentUser->id,
            'recipient_id' => $otherUser->id,
            'offered_skill_id' => $figma->id,
            'requested_skill_id' => $laravel->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);
    }

    public function test_historical_swap_with_null_academic_profile_remains_readable(): void
    {
        $sender = User::factory()->onboarded()->create([
            'name' => 'Historical Sender',
            'program_id' => null,
            'year_level' => null,
        ]);
        $recipient = User::factory()->onboarded()->create(['name' => 'Historical Recipient']);
        $offeredSkill = Skill::create(['name' => 'Legacy Teaching', 'is_approved' => false]);
        $requestedSkill = Skill::create(['name' => 'Legacy Learning', 'is_approved' => false]);
        $sender->teachingSkills()->attach($offeredSkill, ['type' => 'teach']);
        $sender->learningSkills()->attach($requestedSkill, ['type' => 'learn']);
        $recipient->teachingSkills()->attach($requestedSkill, ['type' => 'teach']);
        $recipient->learningSkills()->attach($offeredSkill, ['type' => 'learn']);
        SwapRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'offered_skill_id' => $offeredSkill->id,
            'requested_skill_id' => $requestedSkill->id,
            'status' => SwapRequest::STATUS_PENDING,
        ]);

        $this->actingAs($sender)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Program not set')
            ->assertDontSee('Proficiency')
            ->assertSee('Historical Recipient')
            ->assertSee('Legacy Teaching');
    }

    public function test_custom_skill_names_are_escaped_on_discover_and_match_pages(): void
    {
        $viewer = User::factory()->onboarded()->create();
        $otherUser = User::factory()->onboarded()->create();
        $dangerousName = '<script>alert("skill")</script>';
        $customSkill = Skill::create(['name' => $dangerousName, 'is_approved' => false]);
        $viewer->learningSkills()->attach($customSkill, ['type' => 'learn']);
        $otherUser->teachingSkills()->attach($customSkill, ['type' => 'teach']);

        $this->actingAs($viewer)
            ->get(route('discover.index'))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false);
        $this->actingAs($viewer)
            ->get(route('matches.show', $otherUser))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false);
    }

    /** @return array{User, User, Skill, Skill} */
    private function createCanonicalMatch(): array
    {
        $technology = SkillCategory::create([
            'name' => 'Technology & Programming',
            'slug' => 'technology-programming',
        ]);
        $design = SkillCategory::create([
            'name' => 'Design & Creative',
            'slug' => 'design-creative',
        ]);
        $bsit = Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
        ]);
        $bscs = Program::create([
            'name' => 'Bachelor of Science in Computer Science',
            'abbreviation' => 'BSCS',
        ]);
        $laravel = Skill::create([
            'name' => 'Laravel',
            'skill_category_id' => $technology->id,
            'is_approved' => true,
        ]);
        $figma = Skill::create([
            'name' => 'Figma',
            'skill_category_id' => $design->id,
            'is_approved' => true,
        ]);
        $currentUser = User::factory()->onboarded()->create([
            'program_id' => $bsit->id,
            'year_level' => 4,
        ]);
        $otherUser = User::factory()->onboarded()->create([
            'name' => 'Justine',
            'program_id' => $bscs->id,
            'year_level' => 3,
        ]);
        $currentUser->teachingSkills()->attach($figma, ['type' => 'teach', 'proficiency' => 'intermediate']);
        $currentUser->learningSkills()->attach($laravel, ['type' => 'learn', 'proficiency' => 'beginner']);
        $otherUser->teachingSkills()->attach($laravel, ['type' => 'teach', 'proficiency' => 'advanced']);
        $otherUser->learningSkills()->attach($figma, ['type' => 'learn', 'proficiency' => 'beginner']);

        return [$currentUser, $otherUser, $laravel, $figma];
    }
}
