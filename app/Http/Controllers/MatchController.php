<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function show(Request $request, User $user)
    {
        $currentUser = $request->user();

        // Prevent users from matching with themselves.
        abort_if($currentUser->id === $user->id, 404);

        // Only allow matching with normal users
        // who have completed onboarding.
        abort_unless(
            $user->role === 'user' &&
            $user->onboarding_completed,
            404
        );

        $currentUser->load([
            'teachingSkills',
            'learningSkills',
        ]);

        $user->load([
            'teachingSkills',
            'learningSkills',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Skills you can learn from this user
        |--------------------------------------------------------------------------
        |
        | Your learning skills
        |        ∩
        | Their teaching skills
        |
        */

        $currentLearningIds = $currentUser
            ->learningSkills
            ->pluck('id');

        $skillsYouCanLearn = $user
            ->teachingSkills
            ->whereIn('id', $currentLearningIds)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Skills you can teach this user
        |--------------------------------------------------------------------------
        |
        | Your teaching skills
        |        ∩
        | Their learning skills
        |
        */

        $otherUserLearningIds = $user
            ->learningSkills
            ->pluck('id');

        $skillsYouCanTeach = $currentUser
            ->teachingSkills
            ->whereIn('id', $otherUserLearningIds)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Mutual Match
        |--------------------------------------------------------------------------
        */

        $isMutualMatch =
            $skillsYouCanLearn->isNotEmpty() &&
            $skillsYouCanTeach->isNotEmpty();


        /*
        |--------------------------------------------------------------------------
        | Match Score
        |--------------------------------------------------------------------------
        |
        | Score is based on how much of each user's learning needs
        | can be satisfied by the other person.
        |
        */

        $matchScore = 0;

        if ($isMutualMatch) {

            $learnCoverage =
                $currentUser->learningSkills->count() > 0
                    ? $skillsYouCanLearn->count()
                        / $currentUser->learningSkills->count()
                    : 0;

            $teachCoverage =
                $user->learningSkills->count() > 0
                    ? $skillsYouCanTeach->count()
                        / $user->learningSkills->count()
                    : 0;

            $matchScore = (int) round(
                (($learnCoverage + $teachCoverage) / 2) * 100
            );
        }


        return view('matches.show', compact(
            'user',
            'skillsYouCanLearn',
            'skillsYouCanTeach',
            'isMutualMatch',
            'matchScore'
        ));
    }
}