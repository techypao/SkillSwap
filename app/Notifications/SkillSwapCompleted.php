<?php

namespace App\Notifications;

class SkillSwapCompleted extends SkillSwapNotification
{
    public function type(): string
    {
        return 'skill_session.completed';
    }

    public function message(): string
    {
        return "Your skill swap with {$this->actor->name} is complete";
    }
}
