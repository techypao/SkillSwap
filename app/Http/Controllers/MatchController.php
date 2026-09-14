<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SkillCompatibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function show(Request $request, User $user, SkillCompatibilityService $skillCompatibility): View
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
            'program',
            'teachingSkills.category',
            'learningSkills.category',
        ]);

        $user->load([
            'program',
            'school',
            'teachingSkills.category',
            'learningSkills.category',
            'reviewsReceived' => fn ($query) => $query->with('reviewer')
                ->latest()
                ->take(5),
        ]);
        $user->loadAvg('reviewsReceived', 'rating');
        $user->loadCount('reviewsReceived');

        [
            'skillsYouCanLearn' => $skillsYouCanLearn,
            'skillsYouCanTeach' => $skillsYouCanTeach,
            'isMutualMatch' => $isMutualMatch,
            'matchScore' => $matchScore,
        ] = $skillCompatibility->evaluate($currentUser, $user);

        $pendingSwapRequests = $currentUser->sentSwapRequests()
            ->where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->with(['offeredSkill', 'requestedSkill'])
            ->latest()
            ->get();

        return view('matches.show', compact(
            'user',
            'skillsYouCanLearn',
            'skillsYouCanTeach',
            'isMutualMatch',
            'matchScore',
            'pendingSwapRequests'
        ));
    }
}
