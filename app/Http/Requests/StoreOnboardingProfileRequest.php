<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAcademicProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingProfileRequest extends FormRequest
{
    use ValidatesAcademicProfile;

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
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ...$this->academicProfileRules(),
            'year_level' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'bio' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->academicProfileMessages();
    }
}
