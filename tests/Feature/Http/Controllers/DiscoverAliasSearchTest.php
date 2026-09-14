<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\SkillAliasSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DiscoverAliasSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SkillCategorySeeder::class, CanonicalSkillSeeder::class, SkillAliasSeeder::class]);
    }

    private function skill(string $name): Skill
    {
        return Skill::where('name', $name)->firstOrFail();
    }

    public function test_an_alias_finds_users_who_teach_the_canonical_skill(): void
    {
        $currentUser = User::factory()->onboarded()->create(['name' => 'Searcher']);
        $teacher = User::factory()->onboarded()->create(['name' => 'JavaScript Teacher']);
        $teacher->teachingSkills()->attach($this->skill('JavaScript'), ['type' => 'teach']);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['search' => 'JS', 'type' => 'teach']))
            ->assertOk()
            ->assertSee('JavaScript Teacher');
    }

    public function test_alias_search_does_not_pull_in_related_skills(): void
    {
        $currentUser = User::factory()->onboarded()->create();
        $javaTeacher = User::factory()->onboarded()->create(['name' => 'Java Only Teacher']);
        $javaTeacher->teachingSkills()->attach($this->skill('Java'), ['type' => 'teach']);

        // "JS" is an alias of JavaScript, never of Java.
        $this->actingAs($currentUser)
            ->get(route('discover.index', ['search' => 'JS', 'type' => 'teach']))
            ->assertOk()
            ->assertDontSee('Java Only Teacher');
    }

    public function test_a_laravel_search_does_not_surface_php_only_users(): void
    {
        $currentUser = User::factory()->onboarded()->create();
        $phpTeacher = User::factory()->onboarded()->create(['name' => 'PHP Only Teacher']);
        $phpTeacher->teachingSkills()->attach($this->skill('PHP'), ['type' => 'teach']);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['search' => 'Laravel', 'type' => 'teach']))
            ->assertOk()
            ->assertDontSee('PHP Only Teacher');
    }

    public function test_plain_name_search_still_works(): void
    {
        $currentUser = User::factory()->onboarded()->create();
        $teacher = User::factory()->onboarded()->create(['name' => 'Figma Teacher']);
        $teacher->teachingSkills()->attach($this->skill('Figma'), ['type' => 'teach']);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['search' => 'Figma', 'type' => 'teach']))
            ->assertOk()
            ->assertSee('Figma Teacher');
    }

    public function test_alias_search_works_for_learning_skills_too(): void
    {
        $currentUser = User::factory()->onboarded()->create();
        $learner = User::factory()->onboarded()->create(['name' => 'Excel Learner']);
        $learner->learningSkills()->attach($this->skill('Microsoft Excel'), ['type' => 'learn']);

        $this->actingAs($currentUser)
            ->get(route('discover.index', ['search' => 'Excel', 'type' => 'learn']))
            ->assertOk()
            ->assertSee('Excel Learner');
    }
}
