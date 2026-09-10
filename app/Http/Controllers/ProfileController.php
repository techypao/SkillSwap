<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $user->load([
            'program',
            'teachingSkills.category',
            'learningSkills.category',
            'availabilities',
            'reviewsReceived' => fn ($query) => $query->with('reviewer')
                ->latest()
                ->latest('id'),
            'creditTransactions' => fn ($query) => $query->with('skillSession.swapRequest')
                ->latest()
                ->latest('id')
                ->take(10),
        ]);

        $user->loadAvg('reviewsReceived', 'rating');
        $user->loadCount('reviewsReceived');

        $creditsEarned = (int) $user->creditTransactions()
            ->where('amount', '>', 0)
            ->sum('amount');

        $ratingBreakdown = $user->reviewsReceived
            ->countBy('rating')
            ->all();

        return view('profile.show', compact('user', 'creditsEarned', 'ratingBreakdown'));
    }
}
