<?php

namespace App\Notifications;

class ReviewReceived extends SkillSwapNotification
{
    public function type(): string
    {
        return 'review.received';
    }

    public function message(): string
    {
        return "{$this->actor->name} left you a review";
    }
}
