<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $search = trim($request->input('search', ''));

        $type = $request->input('type', 'teach');

        // Only allow valid filter values.
        if (!in_array($type, ['teach', 'learn', 'all'])) {
            $type = 'teach';
        }

        $users = User::query()
            ->where('id', '!=', $currentUser->id)
            ->where('role', 'user')
            ->where('onboarding_completed', true)
            ->with([
                'teachingSkills',
                'learningSkills',
            ])

            ->when($search !== '', function ($query) use ($search, $type) {

                if ($type === 'teach') {

                    $query->whereHas('teachingSkills', function ($skillQuery) use ($search) {
                        $skillQuery->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        );
                    });

                } elseif ($type === 'learn') {

                    $query->whereHas('learningSkills', function ($skillQuery) use ($search) {
                        $skillQuery->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        );
                    });

                } else {

                    $query->where(function ($query) use ($search) {

                        $query
                            ->whereHas('teachingSkills', function ($skillQuery) use ($search) {
                                $skillQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                );
                            })

                            ->orWhereHas('learningSkills', function ($skillQuery) use ($search) {
                                $skillQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                );
                            });

                    });
                }

            })

            ->orderBy('name')
            ->get();

        return view('discover.index', compact(
            'users',
            'search',
            'type'
        ));
    }
}