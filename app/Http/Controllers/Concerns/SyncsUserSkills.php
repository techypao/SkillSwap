<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\StoreOnboardingSkillGroupRequest;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait SyncsUserSkills
{
    /**
     * Replace one side of the user's skills with the validated selection.
     */
    private function syncSkillGroup(StoreOnboardingSkillGroupRequest $request, string $type): void
    {
        $user = $request->user();
        $group = $request->group();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated, $group, $type): void {
            $skills = $this->selectedSkillIds($validated, $group);

            $this->addSuggestedSkills($skills, $validated, $group, $user->id);

            DB::table('user_skills')
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->delete();

            $this->insertUserSkills($user->id, $type, $skills);
        });
    }

    /**
     * Build the catalog and current selection for one skill picker group.
     *
     * @return array{skills: \Illuminate\Database\Eloquent\Collection<int, Skill>, categories: \Illuminate\Database\Eloquent\Collection<int, SkillCategory>, group: string, selected: list<int>}
     */
    private function skillPickerData(User $user, string $group): array
    {
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

        $relation = $group === 'teaching' ? $user->teachingSkills() : $user->learningSkills();

        $selected = $relation->pluck('skills.id')->all();

        return compact('skills', 'categories', 'group', 'selected');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, int>
     */
    private function selectedSkillIds(array $validated, string $group): Collection
    {
        return collect($validated["{$group}_skills"] ?? [])
            ->mapWithKeys(fn (int|string $skillId): array => [
                (int) $skillId => (int) $skillId,
            ]);
    }

    /**
     * @param  Collection<int, int>  $skills
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

            $skills->put($skill->id, $skill->id);
        }
    }

    /**
     * @param  Collection<int, int>  $skills
     */
    private function insertUserSkills(int $userId, string $type, Collection $skills): void
    {
        $timestamp = now();

        DB::table('user_skills')->insert(
            $skills->map(fn (int $skillId): array => [
                'user_id' => $userId,
                'skill_id' => $skillId,
                'type' => $type,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->values()->all()
        );
    }
}
