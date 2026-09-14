<?php

namespace App\Notifications;

class SwapRequestReceived extends SkillSwapNotification
{
    public function type(): string
    {
        return 'swap_request.received';
    }

    public function message(): string
    {
        return "{$this->actor->name} sent you a swap request";
    }

    public function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
