<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $user->load([
            'teachingSkills',
            'learningSkills',
            'availabilities',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Dashboard placeholders
        |--------------------------------------------------------------------------
        |
        | These features will be implemented later.
        | For now, we send empty collections to the dashboard.
        |
        */

        $recommendedMatches = collect();

        $swapRequests = collect();

        $upcomingSessions = collect();

        return view('dashboard', compact(
            'user',
            'recommendedMatches',
            'swapRequests',
            'upcomingSessions'
        ));
    }
}