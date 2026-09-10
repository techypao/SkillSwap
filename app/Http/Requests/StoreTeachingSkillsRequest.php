<?php

namespace App\Http\Requests;

class StoreTeachingSkillsRequest extends StoreOnboardingSkillGroupRequest
{
    public function group(): string
    {
        return 'teaching';
    }

    protected function emptySelectionMessage(): string
    {
        return 'Please select or suggest at least one skill you can teach.';
    }
}
