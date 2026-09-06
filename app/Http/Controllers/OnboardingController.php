<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function welcome(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.welcome');
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.profile', compact('user'));
    }

    public function storeProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'profile_picture' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'school_organization' => [
                'required',
                'string',
                'max:255',
            ],

            'bio' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] =
                $request->file('profile_picture')
                    ->store('profile-pictures', 'public');
        }

        $user->update($validated);

        return redirect()->route('onboarding.skills');
    }

    public function skills(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if ($user->onboarding_completed) {
        return redirect()->route('dashboard');
    }

    $skills = Skill::where('is_approved', true)
        ->orWhere('created_by', $user->id)
        ->orderBy('name')
        ->get();

    $selectedTeaching = $user->teachingSkills()
        ->pluck('skills.id')
        ->toArray();

    $selectedLearning = $user->learningSkills()
        ->pluck('skills.id')
        ->toArray();

    return view('onboarding.skills', compact(
        'skills',
        'selectedTeaching',
        'selectedLearning'
    ));
}

public function storeSkills(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if ($user->onboarding_completed) {
        return redirect()->route('dashboard');
    }

    $validated = $request->validate([
        'teaching_skills' => [
            'nullable',
            'array',
        ],

        'teaching_skills.*' => [
            'integer',
            'exists:skills,id',
        ],

        'learning_skills' => [
            'nullable',
            'array',
        ],

        'learning_skills.*' => [
            'integer',
            'exists:skills,id',
        ],

        'new_teaching_skills' => [
            'nullable',
            'string',
            'max:500',
        ],

        'new_learning_skills' => [
            'nullable',
            'string',
            'max:500',
        ],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Prepare custom skill names
    |--------------------------------------------------------------------------
    */

    $newTeachingSkills = collect(
        preg_split(
            '/[,\n]+/',
            $validated['new_teaching_skills'] ?? ''
        )
    )
        ->map(fn ($skill) => trim($skill))
        ->filter()
        ->unique();

    $newLearningSkills = collect(
        preg_split(
            '/[,\n]+/',
            $validated['new_learning_skills'] ?? ''
        )
    )
        ->map(fn ($skill) => trim($skill))
        ->filter()
        ->unique();


    /*
    |--------------------------------------------------------------------------
    | Make sure the user has at least one of each
    |--------------------------------------------------------------------------
    */

    $hasTeachingSkill =
        !empty($validated['teaching_skills'] ?? [])
        || $newTeachingSkills->isNotEmpty();

    $hasLearningSkill =
        !empty($validated['learning_skills'] ?? [])
        || $newLearningSkills->isNotEmpty();


    if (!$hasTeachingSkill) {
        return back()
            ->withErrors([
                'teaching_skills' =>
                    'Please select or add at least one skill you can teach.',
            ])
            ->withInput();
    }


    if (!$hasLearningSkill) {
        return back()
            ->withErrors([
                'learning_skills' =>
                    'Please select or add at least one skill you want to learn.',
            ])
            ->withInput();
    }


    /*
    |--------------------------------------------------------------------------
    | Save everything
    |--------------------------------------------------------------------------
    */

    DB::transaction(function () use (
        $user,
        $validated,
        $newTeachingSkills,
        $newLearningSkills
    ) {

        $teachingIds = collect(
            $validated['teaching_skills'] ?? []
        )->map(fn ($id) => (int) $id);


        $learningIds = collect(
            $validated['learning_skills'] ?? []
        )->map(fn ($id) => (int) $id);


        /*
        |--------------------------------------------------------------------------
        | Create new teaching skills
        |--------------------------------------------------------------------------
        */

        foreach ($newTeachingSkills as $skillName) {

            $skill = Skill::whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower($skillName)]
            )->first();

            if (!$skill) {
                $skill = Skill::create([
                    'name' => $skillName,
                    'is_approved' => false,
                    'created_by' => $user->id,
                ]);
            }

            $teachingIds->push($skill->id);
        }


        /*
        |--------------------------------------------------------------------------
        | Create new learning skills
        |--------------------------------------------------------------------------
        */

        foreach ($newLearningSkills as $skillName) {

            $skill = Skill::whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower($skillName)]
            )->first();

            if (!$skill) {
                $skill = Skill::create([
                    'name' => $skillName,
                    'is_approved' => false,
                    'created_by' => $user->id,
                ]);
            }

            $learningIds->push($skill->id);
        }


        $teachingIds = $teachingIds->unique()->values();

        $learningIds = $learningIds->unique()->values();


        /*
        |--------------------------------------------------------------------------
        | Remove previous selections
        |--------------------------------------------------------------------------
        */

        DB::table('user_skills')
            ->where('user_id', $user->id)
            ->where('type', 'teach')
            ->delete();

        DB::table('user_skills')
            ->where('user_id', $user->id)
            ->where('type', 'learn')
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Save teaching skills
        |--------------------------------------------------------------------------
        */

        foreach ($teachingIds as $skillId) {
            DB::table('user_skills')->insert([
                'user_id' => $user->id,
                'skill_id' => $skillId,
                'type' => 'teach',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save learning skills
        |--------------------------------------------------------------------------
        */

        foreach ($learningIds as $skillId) {
            DB::table('user_skills')->insert([
                'user_id' => $user->id,
                'skill_id' => $skillId,
                'type' => 'learn',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });


    return redirect()
        ->route('onboarding.availability')
        ->with('success', 'Your skills were saved successfully!');
}

public function availability(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if ($user->onboarding_completed) {
        return redirect()->route('dashboard');
    }

    // Make sure the user completed the profile step.
    if (!$user->school_organization || !$user->bio) {
        return redirect()->route('onboarding.profile');
    }

    // Make sure the user selected both teaching and learning skills.
    if (
        !$user->teachingSkills()->exists() ||
        !$user->learningSkills()->exists()
    ) {
        return redirect()->route('onboarding.skills');
    }

    $selectedAvailability = $user->availabilities()
        ->get()
        ->map(function ($availability) {
            return $availability->day . '_' . $availability->time_period;
        })
        ->toArray();

    return view(
        'onboarding.availability',
        compact('selectedAvailability')
    );
}

public function storeAvailability(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if ($user->onboarding_completed) {
        return redirect()->route('dashboard');
    }

    // Make sure previous onboarding steps were completed.
    if (!$user->school_organization || !$user->bio) {
        return redirect()->route('onboarding.profile');
    }

    if (
        !$user->teachingSkills()->exists() ||
        !$user->learningSkills()->exists()
    ) {
        return redirect()->route('onboarding.skills');
    }

    $request->validate([
        'availability' => [
            'required',
            'array',
        ],
    ], [
        'availability.required' =>
            'Please select at least one available time.',
    ]);

    $days = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    $timePeriods = [
        'morning',
        'afternoon',
        'evening',
    ];

    $submittedAvailability =
        $request->input('availability', []);

    $rows = [];

    foreach ($days as $day) {

        foreach ($timePeriods as $timePeriod) {

            if (
                isset(
                    $submittedAvailability[$day][$timePeriod]
                )
            ) {
                $rows[] = [
                    'day' => $day,
                    'time_period' => $timePeriod,
                ];
            }

        }

    }

    // Extra protection in case an invalid request is submitted.
    if (empty($rows)) {

        return back()
            ->withErrors([
                'availability' =>
                    'Please select at least one available time.',
            ])
            ->withInput();
    }

    DB::transaction(function () use ($user, $rows) {

        // Remove old availability if the user goes back
        // and changes their selections.
        $user->availabilities()->delete();

        // Save the new selections.
        $user->availabilities()->createMany($rows);

        // Onboarding is officially finished.
        $user->update([
            'onboarding_completed' => true,
        ]);

    });

    return redirect()
        ->route('dashboard')
        ->with(
            'success',
            'Welcome to SkillSwap! Your profile setup is complete.'
        );
}
}