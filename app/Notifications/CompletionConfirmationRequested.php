<?php

namespace App\Notifications;

class CompletionConfirmationRequested extends SkillSwapNotification
{
    public function type(): string
    {
        return 'skill_session.completion_requested';
    }

    public function message(): string
    {
        return "{$this->actor->name} confirmed completion and is waiting for you";
    }
}
