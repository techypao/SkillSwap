<?php

namespace App\Notifications;

class SessionProposed extends SkillSwapNotification
{
    public function type(): string
    {
        return 'skill_session.proposed';
    }

    public function message(): string
    {
        return "{$this->actor->name} proposed a session";
    }
}
