<?php

namespace App\Notifications;

class SessionConfirmed extends SkillSwapNotification
{
    public function type(): string
    {
        return 'skill_session.confirmed';
    }

    public function message(): string
    {
        return "{$this->actor->name} agreed to your session proposal";
    }
}
