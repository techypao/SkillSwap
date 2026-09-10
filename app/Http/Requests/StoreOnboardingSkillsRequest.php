<?php

namespace App\Http\Requests;

use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOnboardingSkillsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $selectableSkill = Rule::exists('skills', 'id')->where(function (Builder $query): void {
            $query->where('is_approved', true)
                ->orWhere('created_by', $this->user()->id);
        });

        return [
            'teaching_skills' => ['nullable', 'array'],
            'teaching_skills.*' => ['integer', 'distinct', $selectableSkill],
            'teaching_proficiencies' => ['nullable', 'array'],
            'teaching_proficiencies.*' => ['nullable', 'string', Rule::in(Skill::PROFICIENCIES)],
            'learning_skills' => ['nullable', 'array'],
            'learning_skills.*' => ['integer', 'distinct', $selectableSkill],
            'learning_proficiencies' => ['nullable', 'array'],
            'learning_proficiencies.*' => ['nullable', 'string', Rule::in(Skill::PROFICIENCIES)],
            'new_teaching_skills' => ['nullable', 'string', 'max:500'],
            'new_teaching_proficiency' => ['nullable', 'string', Rule::in(Skill::PROFICIENCIES)],
            'new_learning_skills' => ['nullable', 'string', 'max:500'],
            'new_learning_proficiency' => ['nullable', 'string', Rule::in(Skill::PROFICIENCIES)],
        ];
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateSkillGroup($validator, 'teaching');
            $this->validateSkillGroup($validator, 'learning');
        }];
    }

    private function validateSkillGroup(Validator $validator, string $group): void
    {
        $selectedSkillIds = $this->input("{$group}_skills", []);
        $proficiencies = $this->input("{$group}_proficiencies", []);
        $selectedSkillIds = is_array($selectedSkillIds) ? $selectedSkillIds : [];
        $proficiencies = is_array($proficiencies) ? $proficiencies : [];
        $customSkills = trim((string) $this->input("new_{$group}_skills", ''));
        $customProficiency = $this->input("new_{$group}_proficiency");

        if ($selectedSkillIds === [] && $customSkills === '') {
            $message = $group === 'teaching'
                ? 'Please select or suggest at least one skill you can teach.'
                : 'Please select or suggest at least one skill you want to learn.';
            $validator->errors()->add("{$group}_skills", $message);
        }

        foreach ($selectedSkillIds as $skillId) {
            if (! in_array($proficiencies[(string) $skillId] ?? null, Skill::PROFICIENCIES, true)) {
                $validator->errors()->add(
                    "{$group}_proficiencies.{$skillId}",
                    'Please select a valid proficiency for every selected skill.'
                );
            }
        }

        if ($customSkills !== '' && ! in_array($customProficiency, Skill::PROFICIENCIES, true)) {
            $validator->errors()->add(
                "new_{$group}_proficiency",
                'Please select a proficiency for your suggested skill.'
            );
        }
    }
}
