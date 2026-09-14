<?php

namespace Tests\Unit;

use App\Support\SkillNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkillNameNormalizerTest extends TestCase
{
    #[DataProvider('normalizationCases')]
    public function test_it_normalizes_search_terms(string $input, string $expected): void
    {
        $this->assertSame($expected, SkillNameNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function normalizationCases(): array
    {
        return [
            'trims surrounding whitespace' => [' JavaScript ', 'javascript'],
            'lowercases' => ['JAVA SCRIPT', 'java script'],
            'collapses repeated whitespace' => ['MS   Excel', 'ms excel'],
            'treats slash as a separator' => ['UI / UX', 'ui ux'],
            'slash without spaces' => ['UI/UX Design', 'ui ux design'],
            'tabs and newlines collapse' => ["Microsoft\t\nWord", 'microsoft word'],
            'empty string stays empty' => ['   ', ''],
        ];
    }

    /**
     * The languages C, C++ and C# must never collapse into one another.
     */
    #[DataProvider('distinctLanguageCases')]
    public function test_it_keeps_meaningful_symbols(string $input, string $expected): void
    {
        $this->assertSame($expected, SkillNameNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function distinctLanguageCases(): array
    {
        return [
            'c' => ['C', 'c'],
            'c plus plus' => ['C++', 'c++'],
            'c sharp' => ['C#', 'c#'],
            'dotted name' => ['Vue.js', 'vue.js'],
        ];
    }

    public function test_the_three_c_languages_normalize_differently(): void
    {
        $c = SkillNameNormalizer::normalize('C');
        $cpp = SkillNameNormalizer::normalize('C++');
        $csharp = SkillNameNormalizer::normalize('C#');

        $this->assertCount(3, array_unique([$c, $cpp, $csharp]));
    }

    public function test_matches_compares_normalized_values(): void
    {
        $this->assertTrue(SkillNameNormalizer::matches(' laravel ', 'Laravel'));
        $this->assertTrue(SkillNameNormalizer::matches('UI / UX Design', 'UI/UX Design'));
        $this->assertFalse(SkillNameNormalizer::matches('C++', 'C#'));
        $this->assertFalse(SkillNameNormalizer::matches('', 'Laravel'));
    }
}
