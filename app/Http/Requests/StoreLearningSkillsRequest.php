<?php

namespace App\Http\Requests;

class StoreLearningSkillsRequest extends StoreOnboardingSkillGroupRequest
{
    public function group(): string
    {
        return 'learning';
    }

    protected function emptySelectionMessage(): string
    {
        return 'Please select or suggest at least one skill you want to learn.';
    }
}
