<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\SkillAlias;
use App\Support\SkillNameNormalizer;
use Illuminate\Database\Seeder;

class SkillAliasSeeder extends Seeder
{
    /**
     * Alternative names students actually type, keyed by canonical skill name.
     *
     * Only unambiguous synonyms and abbreviations belong here. Related-but-
     * different skills (Java vs JavaScript) must never be aliased together.
     *
     * @var array<string, list<string>>
     */
    private const ALIASES = [
        'JavaScript' => ['JS', 'Java Script', 'Javascript'],
        'TypeScript' => ['TS', 'Type Script', 'Typescript'],
        'C++' => ['CPP', 'C Plus Plus'],
        'C#' => ['C Sharp', 'CSharp'],
        'Laravel' => ['Laravel PHP'],
        'React' => ['ReactJS', 'React JS'],
        'Vue.js' => ['Vue', 'VueJS', 'Vue JS'],
        'Node.js' => ['Node', 'NodeJS', 'Node JS'],
        'UI/UX Design' => ['UI UX', 'UX UI', 'UX/UI', 'UI Design', 'UX Design'],
        'Adobe Photoshop' => ['Photoshop', 'PS'],
        'Adobe Illustrator' => ['Illustrator', 'AI Illustrator'],
        'Microsoft Excel' => ['Excel', 'MS Excel'],
        'Microsoft Word' => ['Word', 'MS Word'],
        'Microsoft PowerPoint' => ['PowerPoint', 'PPT', 'MS PowerPoint'],
        'Google Sheets' => ['GSheets', 'Google Spreadsheet'],
        'GitHub' => ['Github'],
        'AutoCAD' => ['Autocad'],
        'Adobe Premiere Pro' => ['Premiere Pro', 'Premiere'],
        'CapCut' => ['Cap Cut'],
        'Database Design' => ['DB Design'],
        'API Development' => ['APIs', 'REST API'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::ALIASES as $canonicalName => $aliases) {
            if ($aliases === []) {
                continue;
            }

            $skill = $this->findCanonicalSkill($canonicalName);

            // A missing canonical skill is skipped rather than invented, so a
            // stray alias can never create a duplicate skill row.
            if ($skill === null) {
                continue;
            }

            foreach ($aliases as $alias) {
                $this->recordAlias($skill, $alias);
            }
        }
    }

    /**
     * Resolve a canonical skill by its stable name, case-insensitively.
     */
    private function findCanonicalSkill(string $canonicalName): ?Skill
    {
        return Skill::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($canonicalName))])
            ->first();
    }

    /**
     * Store an alias once. Re-running the seeder updates rather than duplicates.
     */
    private function recordAlias(Skill $skill, string $alias): void
    {
        $normalized = SkillNameNormalizer::normalize($alias);

        if ($normalized === '') {
            return;
        }

        // An alias that already equals the canonical name adds nothing.
        if ($normalized === SkillNameNormalizer::normalize($skill->name)) {
            return;
        }

        SkillAlias::updateOrCreate(
            ['normalized_alias' => $normalized],
            ['skill_id' => $skill->id, 'alias' => trim($alias)]
        );
    }
}
