<?php

namespace App\Notifications;

class SwapRequestRejected extends SkillSwapNotification
{
    public function type(): string
    {
        return 'swap_request.rejected';
    }

    public function message(): string
    {
        return "{$this->actor->name} declined your swap request";
    }

    /**
     * Rejected swaps have no chat, so point back to the dashboard.
     */
    public function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
