<?php

namespace App\Notifications;

class SessionDeclined extends SkillSwapNotification
{
    public function type(): string
    {
        return 'skill_session.declined';
    }

    public function message(): string
    {
        return "{$this->actor->name} declined your session proposal";
    }
}
