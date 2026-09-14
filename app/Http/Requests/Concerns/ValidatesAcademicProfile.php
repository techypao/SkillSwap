<?php

namespace App\Http\Requests\Concerns;

use App\Models\Program;
use App\Models\School;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Shared academic profile rules for the onboarding and settings forms.
 *
 * A student may select an active catalog entry or provide their own school
 * and program names when the catalog does not contain them.
 */
trait ValidatesAcademicProfile
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function academicProfileRules(): array
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
            'program_id' => [
                'nullable',
                'integer',
                Rule::exists('programs', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true)
                ),
            ],
            'program_name' => ['required_without:program_id', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function academicProfileMessages(): array
    {
        return [
            'school_id.exists' => 'Please select a school from the list or enter your school name.',
            'school_organization.required_without' => 'Please select your school or enter your school name.',
            'program_id.exists' => 'Please select a program from the list or enter your program name.',
            'program_name.required_without' => 'Please select your program or enter your program name.',
        ];
    }

    /**
     * Resolve catalog selections while retaining custom names as fallbacks.
     *
     * @return array<string, mixed>
     */
    public function validatedAcademicProfile(): array
    {
        $validated = $this->validated();
        $school = isset($validated['school_id']) ? School::find($validated['school_id']) : null;
        $program = isset($validated['program_id']) ? Program::find($validated['program_id']) : null;

        $validated['school_id'] = $school?->id;
        $validated['school_organization'] = $school?->name ?? $validated['school_organization'] ?? null;
        $validated['program_id'] = $program?->id;
        $validated['program_name'] = $program?->name ?? $validated['program_name'] ?? null;

        return $validated;
    }
}
