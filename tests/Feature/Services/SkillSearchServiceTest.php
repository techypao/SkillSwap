<?php

namespace Tests\Feature\Services;

use App\Models\Skill;
use App\Models\SkillAlias;
use App\Services\SkillSearchService;
use App\Support\SkillNameNormalizer;
use Database\Seeders\CanonicalSkillSeeder;
use Database\Seeders\SkillAliasSeeder;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SkillSearchServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private SkillSearchService $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SkillCategorySeeder::class, CanonicalSkillSeeder::class, SkillAliasSeeder::class]);

        $this->search = app(SkillSearchService::class);
    }

    /**
     * @return list<string>
     */
    private function names(string $query, int $limit = SkillSearchService::RESULT_LIMIT): array
    {
        return $this->search->search($query, $limit)->pluck('name')->all();
    }

    // ---------------------------------------------------------------- aliases

    #[DataProvider('aliasCases')]
    public function test_aliases_resolve_to_their_canonical_skill(string $query, string $expected): void
    {
        $this->assertSame($expected, $this->names($query)[0] ?? null);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function aliasCases(): array
    {
        return [
            'JS' => ['JS', 'JavaScript'],
            'Java Script' => ['Java Script', 'JavaScript'],
            'TS' => ['TS', 'TypeScript'],
            'Photoshop' => ['Photoshop', 'Adobe Photoshop'],
            'Excel' => ['Excel', 'Microsoft Excel'],
            'MS Excel' => ['MS Excel', 'Microsoft Excel'],
            'PPT' => ['PPT', 'Microsoft PowerPoint'],
            'UI UX' => ['UI UX', 'UI/UX Design'],
            'UX/UI' => ['UX/UI', 'UI/UX Design'],
            'CPP' => ['CPP', 'C++'],
            'C Sharp' => ['C Sharp', 'C#'],
            'NodeJS' => ['NodeJS', 'Node.js'],
        ];
    }

    public function test_java_does_not_resolve_to_javascript(): void
    {
        $this->assertSame('Java', $this->names('Java')[0]);

        // No alias may point Java at JavaScript.
        $javaScript = Skill::where('name', 'JavaScript')->firstOrFail();
        $this->assertSame(
            0,
            SkillAlias::where('skill_id', $javaScript->id)->where('normalized_alias', 'java')->count()
        );
    }

    public function test_alias_seeder_is_idempotent(): void
    {
        $before = SkillAlias::count();

        $this->seed(SkillAliasSeeder::class);
        $this->seed(SkillAliasSeeder::class);

        $this->assertSame($before, SkillAlias::count());
        $this->assertGreaterThan(0, $before);
    }

    public function test_alias_rows_are_never_returned_as_selectable_skills(): void
    {
        $results = $this->search->search('JS');

        $this->assertNotEmpty($results);
        $this->assertContains('JavaScript', $results->pluck('name')->all());
        $this->assertNotContains('JS', $results->pluck('name')->all());

        // Every result is a real canonical Skill row.
        foreach ($results as $result) {
            $this->assertInstanceOf(Skill::class, $result);
            $this->assertDatabaseHas('skills', ['id' => $result->id, 'is_approved' => true]);
        }
    }

    // ------------------------------------------------------------------ typos

    #[DataProvider('typoCases')]
    public function test_typos_suggest_the_intended_skill(string $query, string $expected): void
    {
        $this->assertSame($expected, $this->names($query)[0] ?? null);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function typoCases(): array
    {
        return [
            'larvel' => ['larvel', 'Laravel'],
            'pyhton (transposition)' => ['pyhton', 'Python'],
            'javascrpt' => ['javascrpt', 'JavaScript'],
            'photoshp (via alias)' => ['photoshp', 'Adobe Photoshop'],
            'figmaa' => ['figmaa', 'Figma'],
            'typescipt' => ['typescipt', 'TypeScript'],
        ];
    }

    #[DataProvider('unsafeShortQueries')]
    public function test_short_queries_are_never_fuzzy_corrected(string $query): void
    {
        $needle = SkillNameNormalizer::normalize($query);

        // Short queries may still surface literal substring matches, but never a
        // guess: every result must actually contain the term in its name or one
        // of its aliases.
        foreach ($this->search->search($query) as $skill) {
            $haystacks = $skill->aliases
                ->map(fn (SkillAlias $alias): string => $alias->normalized_alias)
                ->push(SkillNameNormalizer::normalize($skill->name))
                ->all();

            $containsNeedle = collect($haystacks)
                ->contains(fn (string $value): bool => str_contains($value, $needle));

            $this->assertTrue(
                $containsNeedle,
                "[{$query}] produced an unsafe fuzzy correction: {$skill->name}"
            );
        }
    }

    public function test_queries_below_the_minimum_length_never_reach_the_fuzzy_tier(): void
    {
        // Each is one edit from a real skill but too short to be "corrected".
        $this->assertNotContains('Python', $this->names('pyh'));
        $this->assertNotContains('Java', $this->names('jva'));
        $this->assertNotContains('Git', $this->names('gt'));
    }

    public function test_fuzzy_matching_begins_at_the_minimum_length(): void
    {
        // Four characters is the documented threshold, so a single-edit slip
        // at that length is corrected while three characters is not.
        $this->assertSame(SkillSearchService::MIN_FUZZY_LENGTH, 4);
        $this->assertContains('Java', $this->names('jvaa'));
        $this->assertNotContains('Java', $this->names('jva'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeShortQueries(): array
    {
        return ['single letter' => ['J'], 'two letters' => ['Ja'], 'three letters' => ['Jav']];
    }

    public function test_results_are_capped(): void
    {
        $this->assertLessThanOrEqual(
            SkillSearchService::RESULT_LIMIT,
            $this->search->search('a')->count()
        );

        $this->assertCount(3, $this->search->search('a', 3));
    }

    public function test_exact_name_outranks_alias_and_partial_matches(): void
    {
        // "C" is an exact canonical name, and must beat C++ / C# prefixes.
        $this->assertSame('C', $this->names('C')[0]);
    }

    public function test_blank_query_returns_nothing(): void
    {
        $this->assertTrue($this->search->search('   ')->isEmpty());
    }

    public function test_unapproved_custom_skills_are_not_searchable(): void
    {
        Skill::create(['name' => 'Totally Custom Thing', 'is_approved' => false]);

        $this->assertSame([], $this->names('Totally Custom Thing'));
    }

    // ---------------------------------------------------------- strong match

    #[DataProvider('strongMatchCases')]
    public function test_strong_match_identifies_duplicates(string $query, ?string $expected): void
    {
        $match = $this->search->strongMatch($query);

        $this->assertSame($expected, $match?->name);
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function strongMatchCases(): array
    {
        return [
            'exact canonical' => ['Laravel', 'Laravel'],
            'case variant' => ['LARAVEL', 'Laravel'],
            'padded' => ['  Laravel  ', 'Laravel'],
            'exact alias' => ['Photoshop', 'Adobe Photoshop'],
            'alias with prefix' => ['MS Excel', 'Microsoft Excel'],
            'strong typo' => ['larvel', 'Laravel'],
            'genuinely new skill' => ['Blender', null],
            'another new skill' => ['Crocheting', null],
            'multi word new skill' => ['Campus Event Hosting', null],
        ];
    }

    public function test_alias_skill_ids_resolves_exact_aliases_only(): void
    {
        $javaScript = Skill::where('name', 'JavaScript')->firstOrFail();

        $this->assertSame([$javaScript->id], $this->search->aliasSkillIds('JS'));
        $this->assertSame([$javaScript->id], $this->search->aliasSkillIds('  js  '));

        // A typo is not an exact alias, so the browse filter stays narrow.
        $this->assertSame([], $this->search->aliasSkillIds('jss'));
        $this->assertSame([], $this->search->aliasSkillIds(''));
    }
}
