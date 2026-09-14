<?php

namespace App\Notifications;

class SwapMessageReceived extends SkillSwapNotification
{
    public function type(): string
    {
        return 'swap_message.received';
    }

    public function message(): string
    {
        return "{$this->actor->name} sent you a message";
    }
}
