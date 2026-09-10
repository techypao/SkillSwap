<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileSettingsRequest;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('settings.edit', compact('user', 'programs'));
    }

    public function update(UpdateProfileSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $removeProfilePicture = (bool) ($validated['remove_profile_picture'] ?? false);

        unset($validated['remove_profile_picture']);

        if ($request->hasFile('profile_picture')) {
            $this->deleteProfilePicture($user->profile_picture);

            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        } elseif ($removeProfilePicture) {
            $this->deleteProfilePicture($user->profile_picture);

            $validated['profile_picture'] = null;
        } else {
            unset($validated['profile_picture']);
        }

        $user->update($validated);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Your profile was updated successfully!');
    }

    private function deleteProfilePicture(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
