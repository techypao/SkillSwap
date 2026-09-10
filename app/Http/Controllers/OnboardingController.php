<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SyncsUserSkills;
use App\Http\Requests\StoreLearningSkillsRequest;
use App\Http\Requests\StoreOnboardingProfileRequest;
use App\Http\Requests\StoreTeachingSkillsRequest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    use SyncsUserSkills;

    public function welcome(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        return view('onboarding.welcome');
    }

    public function profile(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();
        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('onboarding.profile', compact('user', 'programs'));
    }

    public function storeProfile(StoreOnboardingProfileRequest $request): RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $validated = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $request->user()->update($validated);

        return redirect()->route('onboarding.skills.teach');
    }

    public function teachSkills(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();

        if (! $this->hasProfileDetails($user)) {
            return redirect()->route('onboarding.profile');
        }

        return view('onboarding.skills', [
            ...$this->skillPickerData($user, 'teaching'),
            'step' => 3,
            'title' => 'What are your skills?',
            'intro' => 'Pick the skills you can teach other students.',
            'heading' => 'What can you teach?',
            'formAction' => route('onboarding.skills.teach.store'),
            'backRoute' => route('onboarding.profile'),
        ]);
    }

    public function storeTeachSkills(StoreTeachingSkillsRequest $request): RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $this->syncSkillGroup($request, 'teach');

        return redirect()
            ->route('onboarding.skills.learn')
            ->with('success', 'Your teaching skills were saved successfully!');
    }

    public function learnSkills(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();

        if (! $this->hasProfileDetails($user)) {
            return redirect()->route('onboarding.profile');
        }

        if (! $user->teachingSkills()->exists()) {
            return redirect()->route('onboarding.skills.teach');
        }

        return view('onboarding.skills', [
            ...$this->skillPickerData($user, 'learning'),
            'step' => 4,
            'title' => 'What do you want to learn?',
            'intro' => 'Pick the skills you want to learn from other students.',
            'heading' => 'What do you want to learn?',
            'formAction' => route('onboarding.skills.learn.store'),
            'backRoute' => route('onboarding.skills.teach'),
        ]);
    }

    public function storeLearnSkills(StoreLearningSkillsRequest $request): RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $this->syncSkillGroup($request, 'learn');

        return redirect()
            ->route('onboarding.availability')
            ->with('success', 'Your learning goals were saved successfully!');
    }

    public function availability(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();

        if (! $this->hasProfileDetails($user)) {
            return redirect()->route('onboarding.profile');
        }

        if (! $user->teachingSkills()->exists()) {
            return redirect()->route('onboarding.skills.teach');
        }

        if (! $user->learningSkills()->exists()) {
            return redirect()->route('onboarding.skills.learn');
        }

        $selectedAvailability = $user->availabilities()
            ->get()
            ->map(fn ($availability): string => $availability->day.'_'.$availability->time_period)
            ->all();

        return view('onboarding.availability', compact('selectedAvailability'));
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();

        if (! $this->hasProfileDetails($user)) {
            return redirect()->route('onboarding.profile');
        }

        if (! $user->teachingSkills()->exists()) {
            return redirect()->route('onboarding.skills.teach');
        }

        if (! $user->learningSkills()->exists()) {
            return redirect()->route('onboarding.skills.learn');
        }

        $request->validate([
            'availability' => ['required', 'array'],
        ], [
            'availability.required' => 'Please select at least one available time.',
        ]);

        $submittedAvailability = $request->input('availability', []);
        $rows = [];

        foreach ($this->days() as $day) {
            foreach (['morning', 'afternoon', 'evening'] as $timePeriod) {
                if (isset($submittedAvailability[$day][$timePeriod])) {
                    $rows[] = ['day' => $day, 'time_period' => $timePeriod];
                }
            }
        }

        if ($rows === []) {
            return back()
                ->withErrors(['availability' => 'Please select at least one available time.'])
                ->withInput();
        }

        DB::transaction(function () use ($user, $rows): void {
            $user->availabilities()->delete();
            $user->availabilities()->createMany($rows);
            $user->update(['onboarding_completed' => true]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome to SkillSwap! Your profile setup is complete.');
    }

    private function onboardingRedirect(Request $request): ?RedirectResponse
    {
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return null;
    }

    private function hasProfileDetails(User $user): bool
    {
        return (bool) ($user->school_organization
            && $user->program_id
            && $user->year_level
            && $user->bio);
    }

    /**
     * @return list<string>
     */
    private function days(): array
    {
        return [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];
    }
}
