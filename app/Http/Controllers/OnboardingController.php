<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnboardingProfileRequest;
use App\Http\Requests\StoreOnboardingSkillsRequest;
use App\Models\Program;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
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

        return redirect()->route('onboarding.skills');
    }

    public function skills(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();
        $skills = Skill::query()
            ->with('category')
            ->where(function ($query) use ($user): void {
                $query->where('is_approved', true)
                    ->orWhere('created_by', $user->id);
            })
            ->orderBy('name')
            ->get();
        $categories = SkillCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $selectedTeaching = $user->teachingSkills()
            ->get()
            ->mapWithKeys(fn (Skill $skill): array => [
                $skill->id => $skill->pivot->proficiency,
            ])
            ->all();
        $selectedLearning = $user->learningSkills()
            ->get()
            ->mapWithKeys(fn (Skill $skill): array => [
                $skill->id => $skill->pivot->proficiency,
            ])
            ->all();

        return view('onboarding.skills', compact(
            'skills',
            'categories',
            'selectedTeaching',
            'selectedLearning'
        ));
    }

    public function storeSkills(StoreOnboardingSkillsRequest $request): RedirectResponse
    {
        if ($redirect = $this->onboardingRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $teachingSkills = $this->selectedSkillsWithProficiency($validated, 'teaching');
            $learningSkills = $this->selectedSkillsWithProficiency($validated, 'learning');

            $this->addSuggestedSkills($teachingSkills, $validated, 'teaching', $user->id);
            $this->addSuggestedSkills($learningSkills, $validated, 'learning', $user->id);

            DB::table('user_skills')
                ->where('user_id', $user->id)
                ->where('type', 'teach')
                ->delete();
            DB::table('user_skills')
                ->where('user_id', $user->id)
                ->where('type', 'learn')
                ->delete();

            $this->insertUserSkills($user->id, 'teach', $teachingSkills);
            $this->insertUserSkills($user->id, 'learn', $learningSkills);
        });

        return redirect()
            ->route('onboarding.availability')
            ->with('success', 'Your skills were saved successfully!');
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

        if (! $user->teachingSkills()->exists() || ! $user->learningSkills()->exists()) {
            return redirect()->route('onboarding.skills');
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

        if (! $user->teachingSkills()->exists() || ! $user->learningSkills()->exists()) {
            return redirect()->route('onboarding.skills');
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

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, array{skill_id: int, proficiency: string}>
     */
    private function selectedSkillsWithProficiency(array $validated, string $group): Collection
    {
        $proficiencies = $validated["{$group}_proficiencies"] ?? [];

        return collect($validated["{$group}_skills"] ?? [])
            ->mapWithKeys(fn (int|string $skillId): array => [
                (int) $skillId => [
                    'skill_id' => (int) $skillId,
                    'proficiency' => $proficiencies[(string) $skillId],
                ],
            ]);
    }

    /**
     * @param  Collection<int, array{skill_id: int, proficiency: string}>  $skills
     * @param  array<string, mixed>  $validated
     */
    private function addSuggestedSkills(
        Collection $skills,
        array $validated,
        string $group,
        int $userId
    ): void {
        $skillNames = collect(preg_split('/[,\n]+/', $validated["new_{$group}_skills"] ?? ''))
            ->map(fn (string $skillName): string => trim($skillName))
            ->filter()
            ->unique(fn (string $skillName): string => mb_strtolower($skillName));

        foreach ($skillNames as $skillName) {
            $skill = Skill::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($skillName)])
                ->first();

            if (! $skill) {
                $skill = Skill::create([
                    'name' => $skillName,
                    'is_approved' => false,
                    'created_by' => $userId,
                ]);
            }

            $skills->put($skill->id, [
                'skill_id' => $skill->id,
                'proficiency' => $validated["new_{$group}_proficiency"],
            ]);
        }
    }

    /**
     * @param  Collection<int, array{skill_id: int, proficiency: string}>  $skills
     */
    private function insertUserSkills(int $userId, string $type, Collection $skills): void
    {
        $timestamp = now();

        DB::table('user_skills')->insert(
            $skills->map(fn (array $skill): array => [
                'user_id' => $userId,
                'skill_id' => $skill['skill_id'],
                'type' => $type,
                'proficiency' => $skill['proficiency'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->values()->all()
        );
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
