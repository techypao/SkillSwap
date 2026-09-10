<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Program;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_program_and_category_seeders_are_idempotent(): void
    {
        $this->seed([ProgramSeeder::class, SkillCategorySeeder::class]);
        $this->seed([ProgramSeeder::class, SkillCategorySeeder::class]);

        $this->assertSame(11, Program::count());
        $this->assertSame(11, SkillCategory::count());
        $this->assertSame(11, Program::where('is_active', true)->count());
        $this->assertSame(11, SkillCategory::where('is_active', true)->count());
    }

    public function test_canonical_skill_seeder_is_idempotent_and_approves_catalog(): void
    {
        $this->seed(SkillCategorySeeder::class);
        $this->seed(CanonicalSkillSeeder::class);
        $this->seed(CanonicalSkillSeeder::class);

        $this->assertSame(88, Skill::count());
        $this->assertSame(88, Skill::where('is_approved', true)->count());
        $this->assertSame(88, Skill::whereNotNull('skill_category_id')->count());
    }

    public function test_existing_case_insensitive_skill_is_reused_without_changing_its_id_or_name(): void
    {
        $creator = User::factory()->create();
        $existingSkill = Skill::create([
            'name' => 'LARAVEL',
            'is_approved' => false,
            'created_by' => $creator->id,
        ]);

        $this->seed(SkillCategorySeeder::class);
        $this->seed(CanonicalSkillSeeder::class);

        $existingSkill->refresh();
        $this->assertSame('LARAVEL', $existingSkill->name);
        $this->assertSame($creator->id, $existingSkill->created_by);
        $this->assertTrue($existingSkill->is_approved);
        $this->assertNotNull($existingSkill->skill_category_id);
        $this->assertSame(1, Skill::query()->whereRaw('LOWER(name) = ?', ['laravel'])->count());
    }

    public function test_noncanonical_custom_skill_and_existing_relationship_remain_intact(): void
    {
        $user = User::factory()->create();
        $customSkill = Skill::create([
            'name' => 'Campus Event Hosting',
            'is_approved' => false,
            'created_by' => $user->id,
        ]);
        $user->teachingSkills()->attach($customSkill, ['type' => 'teach']);

        $this->seed(SkillCategorySeeder::class);
        $this->seed(CanonicalSkillSeeder::class);

        $this->assertDatabaseHas('skills', [
            'id' => $customSkill->id,
            'name' => 'Campus Event Hosting',
            'is_approved' => false,
            'skill_category_id' => null,
        ]);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $customSkill->id,
            'type' => 'teach',
            'proficiency' => null,
        ]);
    }
}
