<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single authoritative two-way compatibility rule.
 *
 * Compatibility is exact canonical skill-id equality in both directions; it
 * never uses aliases or related skills. Expects both users' teachingSkills and
 * learningSkills relations to be loaded (they are lazy-loaded otherwise).
 */
class SkillCompatibilityService
{
    /**
     * @return array{skillsYouCanLearn: Collection, skillsYouCanTeach: Collection, isMutualMatch: bool, matchScore: int}
     */
    public function evaluate(User $currentUser, User $otherUser): array
    {
        // Your learning skills ∩ their teaching skills.
        $skillsYouCanLearn = $otherUser
            ->teachingSkills
            ->whereIn('id', $currentUser->learningSkills->pluck('id'))
            ->values();

        // Your teaching skills ∩ their learning skills.
        $skillsYouCanTeach = $currentUser
            ->teachingSkills
            ->whereIn('id', $otherUser->learningSkills->pluck('id'))
            ->values();

        $isMutualMatch = $skillsYouCanLearn->isNotEmpty() && $skillsYouCanTeach->isNotEmpty();

        // Score is how much of each user's learning needs the other person can satisfy.
        $matchScore = 0;

        if ($isMutualMatch) {
            $learnCoverage = $currentUser->learningSkills->count() > 0
                ? $skillsYouCanLearn->count() / $currentUser->learningSkills->count()
                : 0;

            $teachCoverage = $otherUser->learningSkills->count() > 0
                ? $skillsYouCanTeach->count() / $otherUser->learningSkills->count()
                : 0;

            $matchScore = (int) round((($learnCoverage + $teachCoverage) / 2) * 100);
        }

        return [
            'skillsYouCanLearn' => $skillsYouCanLearn,
            'skillsYouCanTeach' => $skillsYouCanTeach,
            'isMutualMatch' => $isMutualMatch,
            'matchScore' => $matchScore,
        ];
    }
}
