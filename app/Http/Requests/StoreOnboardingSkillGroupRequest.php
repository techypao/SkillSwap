<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class StoreOnboardingSkillGroupRequest extends FormRequest
{
    /**
     * The onboarding skill group this request validates.
     */
    abstract public function group(): string;

    /**
     * The error message shown when no skill was selected or suggested.
     */
    abstract protected function emptySelectionMessage(): string;

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

        $group = $this->group();

        return [
            "{$group}_skills" => ['nullable', 'array'],
            "{$group}_skills.*" => ['integer', 'distinct', $selectableSkill],
            "new_{$group}_skills" => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $group = $this->group();

            $selectedSkillIds = $this->input("{$group}_skills", []);
            $selectedSkillIds = is_array($selectedSkillIds) ? $selectedSkillIds : [];
            $customSkills = trim((string) $this->input("new_{$group}_skills", ''));

            if ($selectedSkillIds === [] && $customSkills === '') {
                $validator->errors()->add("{$group}_skills", $this->emptySelectionMessage());
            }
        }];
    }
}
