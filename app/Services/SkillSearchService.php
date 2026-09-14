<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\SkillAlias;
use App\Support\SkillNameNormalizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Typo-tolerant lookup over the canonical skill catalog.
 *
 * Search quality only: this never changes what a match *means*. Every result is
 * a canonical Skill record, so whatever the user selects is stored as a
 * canonical skill id exactly as before. Aliases are never returned as
 * selectable skills of their own.
 */
class SkillSearchService
{
    public const RESULT_LIMIT = 10;

    /** Queries shorter than this are never fuzzy-matched. */
    public const MIN_FUZZY_LENGTH = 4;

    /** Ranking tiers, lowest wins. */
    private const RANK_EXACT_NAME = 1;

    private const RANK_EXACT_ALIAS = 2;

    private const RANK_NAME_PREFIX = 3;

    private const RANK_ALIAS_PREFIX = 4;

    private const RANK_NAME_CONTAINS = 5;

    private const RANK_ALIAS_CONTAINS = 6;

    private const RANK_FUZZY = 7;

    /**
     * Ranked canonical skills for a search term.
     *
     * @return Collection<int, Skill>
     */
    public function search(string $query, int $limit = self::RESULT_LIMIT): Collection
    {
        $normalized = SkillNameNormalizer::normalize($query);

        if ($normalized === '') {
            return collect();
        }

        return $this->rank($normalized, $this->searchableSkills())
            ->take($limit)
            ->map(fn (array $scored): Skill => $scored['skill'])
            ->values();
    }

    /**
     * The single best canonical skill for a term, only when confidence is high.
     *
     * Used to stop a custom suggestion from duplicating something that already
     * exists under another name. Returns null for weak similarity so genuinely
     * new skills stay suggestable.
     */
    public function strongMatch(string $query): ?Skill
    {
        $normalized = SkillNameNormalizer::normalize($query);

        if ($normalized === '') {
            return null;
        }

        $best = $this->rank($normalized, $this->searchableSkills())->first();

        if ($best === null) {
            return null;
        }

        // Exact canonical name or exact alias is always conclusive.
        if ($best['rank'] <= self::RANK_EXACT_ALIAS) {
            return $best['skill'];
        }

        // Otherwise only a near-certain typo counts, and only for longer terms.
        $isStrongTypo = $best['rank'] === self::RANK_FUZZY
            && $best['distance'] <= 1
            && mb_strlen($normalized) >= 5;

        return $isStrongTypo ? $best['skill'] : null;
    }

    /**
     * Canonical skill ids whose alias exactly equals the term.
     *
     * Intentionally exact-only: used to widen a browse filter, never to widen
     * what counts as a compatibility match.
     *
     * @return list<int>
     */
    public function aliasSkillIds(string $query): array
    {
        $normalized = SkillNameNormalizer::normalize($query);

        if ($normalized === '') {
            return [];
        }

        return SkillAlias::query()
            ->where('normalized_alias', $normalized)
            ->pluck('skill_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Approved canonical skills, with the data ranking needs.
     *
     * @return EloquentCollection<int, Skill>
     */
    private function searchableSkills(): EloquentCollection
    {
        return Skill::query()
            ->with(['category', 'aliases'])
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Score every candidate and drop the non-matches.
     *
     * @param  EloquentCollection<int, Skill>  $skills
     * @return Collection<int, array{skill: Skill, rank: int, distance: int}>
     */
    private function rank(string $normalized, EloquentCollection $skills): Collection
    {
        $maxDistance = $this->maxDistanceFor(mb_strlen($normalized));

        return $skills
            ->map(function (Skill $skill) use ($normalized, $maxDistance): ?array {
                $scored = $this->scoreSkill($skill, $normalized, $maxDistance);

                return $scored === null ? null : ['skill' => $skill] + $scored;
            })
            ->filter()
            ->sortBy([
                fn (array $a, array $b): int => $a['rank'] <=> $b['rank'],
                fn (array $a, array $b): int => $a['distance'] <=> $b['distance'],
                fn (array $a, array $b): int => strcmp($a['skill']->name, $b['skill']->name),
            ])
            ->values();
    }

    /**
     * Best tier for one skill across its canonical name and every alias.
     *
     * @return array{rank: int, distance: int}|null
     */
    private function scoreSkill(Skill $skill, string $normalized, int $maxDistance): ?array
    {
        $name = SkillNameNormalizer::normalize($skill->name);
        $aliases = $skill->aliases
            ->map(fn ($alias): string => (string) $alias->normalized_alias)
            ->filter()
            ->values()
            ->all();

        if ($name === $normalized) {
            return ['rank' => self::RANK_EXACT_NAME, 'distance' => 0];
        }

        if (in_array($normalized, $aliases, true)) {
            return ['rank' => self::RANK_EXACT_ALIAS, 'distance' => 0];
        }

        if (str_starts_with($name, $normalized)) {
            return ['rank' => self::RANK_NAME_PREFIX, 'distance' => 0];
        }

        foreach ($aliases as $alias) {
            if (str_starts_with($alias, $normalized)) {
                return ['rank' => self::RANK_ALIAS_PREFIX, 'distance' => 0];
            }
        }

        if (str_contains($name, $normalized)) {
            return ['rank' => self::RANK_NAME_CONTAINS, 'distance' => 0];
        }

        foreach ($aliases as $alias) {
            if (str_contains($alias, $normalized)) {
                return ['rank' => self::RANK_ALIAS_CONTAINS, 'distance' => 0];
            }
        }

        if ($maxDistance === 0) {
            return null;
        }

        $distance = $this->bestDistance($normalized, [$name, ...$aliases], $maxDistance);

        return $distance === null
            ? null
            : ['rank' => self::RANK_FUZZY, 'distance' => $distance];
    }

    /**
     * Smallest acceptable edit distance across a set of candidate strings.
     *
     * @param  list<string>  $candidates
     */
    private function bestDistance(string $needle, array $candidates, int $maxDistance): ?int
    {
        $best = null;

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            // A candidate whose length is wildly different can never qualify.
            if (abs(mb_strlen($candidate) - mb_strlen($needle)) > $maxDistance) {
                continue;
            }

            $distance = $this->editDistance($needle, $candidate);

            if ($distance <= $maxDistance && ($best === null || $distance < $best)) {
                $best = $distance;
            }
        }

        return $best;
    }

    /**
     * How far a term may stray before it stops being a plausible typo.
     *
     * Short queries are excluded entirely: "J" must never be "corrected" into
     * Java, JavaScript or Japanese.
     */
    private function maxDistanceFor(int $length): int
    {
        if ($length < self::MIN_FUZZY_LENGTH) {
            return 0;
        }

        return $length <= 5 ? 1 : 2;
    }

    /**
     * Optimal string alignment distance.
     *
     * Levenshtein plus adjacent transpositions, so a swapped-letter slip like
     * "pyhton" costs one edit rather than two.
     */
    private function editDistance(string $first, string $second): int
    {
        $a = (array) preg_split('//u', $first, -1, PREG_SPLIT_NO_EMPTY);
        $b = (array) preg_split('//u', $second, -1, PREG_SPLIT_NO_EMPTY);
        $lengthA = count($a);
        $lengthB = count($b);

        if ($lengthA === 0 || $lengthB === 0) {
            return max($lengthA, $lengthB);
        }

        $matrix = [];

        for ($i = 0; $i <= $lengthA; $i++) {
            $matrix[$i][0] = $i;
        }

        for ($j = 0; $j <= $lengthB; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $lengthA; $i++) {
            for ($j = 1; $j <= $lengthB; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;

                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,
                    $matrix[$i][$j - 1] + 1,
                    $matrix[$i - 1][$j - 1] + $cost
                );

                if ($i > 1 && $j > 1
                    && $a[$i - 1] === $b[$j - 2]
                    && $a[$i - 2] === $b[$j - 1]
                ) {
                    $matrix[$i][$j] = min($matrix[$i][$j], $matrix[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $matrix[$lengthA][$lengthB];
    }
}
