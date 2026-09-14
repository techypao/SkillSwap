<?php

namespace App\Http\Requests\Concerns;

use App\Models\School;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Shared school rules for the onboarding and settings profile forms.
 *
 * A student either picks a canonical school (school_id) or types one manually
 * (school_organization). The hidden school_id is never trusted: it must point
 * at an existing, active school.
 */
trait ValidatesSchoolSelection
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function schoolRules(): array
    {
        return [
            'school_id' => [
                'nullable',
                'integer',
                Rule::exists('schools', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true)
                ),
            ],
            'school_organization' => ['required_without:school_id', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function schoolMessages(): array
    {
        return [
            'school_id.exists' => 'Please select a school from the list or enter it manually.',
            'school_organization.required_without' => 'Please select your school or enter it manually.',
        ];
    }

    /**
     * Validated data with the school choice resolved.
     *
     * Canonical pick: school_id is kept and school_organization mirrors the
     * canonical name for backward compatibility. Manual entry: school_id is null.
     *
     * @return array<string, mixed>
     */
    public function validatedWithSchool(): array
    {
        $validated = $this->validated();
        $school = isset($validated['school_id']) ? School::find($validated['school_id']) : null;

        $validated['school_id'] = $school?->id;
        $validated['school_organization'] = $school?->name ?? $validated['school_organization'];

        return $validated;
    }
}
