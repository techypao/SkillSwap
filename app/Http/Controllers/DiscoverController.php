<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SkillCompatibilityService;
use App\Services\SkillSearchService;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function index(Request $request, SkillCompatibilityService $skillCompatibility)
    {
        $currentUser = $request->user();

        $search = trim($request->input('search', ''));

        $type = $request->input('type', 'teach');

        $matchesOnly = $request->input('mode') === 'matches';

        // Aliases widen this browse filter only: "JS" also finds JavaScript
        // users. Compatibility matching is untouched by this.
        $aliasSkillIds = $search === ''
            ? []
            : app(SkillSearchService::class)->aliasSkillIds($search);

        $matchesSkill = function ($skillQuery) use ($search, $aliasSkillIds): void {
            $skillQuery->where(function ($nameQuery) use ($search, $aliasSkillIds): void {
                $nameQuery->where('name', 'like', '%'.$search.'%');

                if ($aliasSkillIds !== []) {
                    $nameQuery->orWhereIn('skills.id', $aliasSkillIds);
                }
            });
        };

        // Only allow valid filter values.
        if (! in_array($type, ['teach', 'learn', 'all'])) {
            $type = 'teach';
        }

        $users = User::query()
            ->where('id', '!=', $currentUser->id)
            ->where('role', 'user')
            ->where('onboarding_completed', true)
            ->with([
                'program',
                'school',
                'teachingSkills.category',
                'learningSkills.category',
            ])
            ->withAvg('reviewsReceived', 'rating')
            ->withCount('reviewsReceived')

            ->when($search !== '', function ($query) use ($type, $matchesSkill) {

                if ($type === 'teach') {

                    $query->whereHas('teachingSkills', $matchesSkill);

                } elseif ($type === 'learn') {

                    $query->whereHas('learningSkills', $matchesSkill);

                } else {

                    $query->where(function ($query) use ($matchesSkill) {

                        $query
                            ->whereHas('teachingSkills', $matchesSkill)
                            ->orWhereHas('learningSkills', $matchesSkill);

                    });
                }

            })

            ->orderBy('name')
            ->get();

        // Every card is tagged using the exact compatibility rule from the match
        // page, so the view can toggle "Matches Only" without reloading.
        $currentUser->loadMissing(['teachingSkills', 'learningSkills']);

        $compatibilities = [];

        foreach ($users as $user) {
            $compatibilities[$user->id] = $skillCompatibility->evaluate($currentUser, $user);
        }

        $matchCount = collect($compatibilities)->where('isMutualMatch', true)->count();

        return view('discover.index', compact(
            'users',
            'search',
            'type',
            'matchesOnly',
            'compatibilities',
            'matchCount'
        ));
    }
}
