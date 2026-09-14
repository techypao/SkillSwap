<?php

namespace App\Notifications;

class SwapRequestAccepted extends SkillSwapNotification
{
    public function type(): string
    {
        return 'swap_request.accepted';
    }

    public function message(): string
    {
        return "{$this->actor->name} accepted your swap request";
    }
}
