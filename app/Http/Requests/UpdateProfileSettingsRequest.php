<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileSettingsRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
            'school_organization' => ['required', 'string', 'max:255'],
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true)
                ),
            ],
            'year_level' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'bio' => ['required', 'string', 'max:500'],
        ];
    }
}
