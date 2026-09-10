<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SyncsUserSkills;
use App\Http\Requests\StoreLearningSkillsRequest;
use App\Http\Requests\StoreTeachingSkillsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsSkillController extends Controller
{
    use SyncsUserSkills;

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('settings.skills', [
            'teaching' => $this->skillPickerData($user, 'teaching'),
            'learning' => $this->skillPickerData($user, 'learning'),
        ]);
    }

    public function updateTeaching(StoreTeachingSkillsRequest $request): RedirectResponse
    {
        $this->syncSkillGroup($request, 'teach');

        return redirect()
            ->route('settings.skills.edit')
            ->with('success', 'Your teaching skills were updated successfully!');
    }

    public function updateLearning(StoreLearningSkillsRequest $request): RedirectResponse
    {
        $this->syncSkillGroup($request, 'learn');

        return redirect()
            ->route('settings.skills.edit')
            ->with('success', 'Your learning goals were updated successfully!');
    }
}
