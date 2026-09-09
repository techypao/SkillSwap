<?php

namespace App\Policies;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;

class SkillSessionPolicy
{
    public function respond(User $user, SkillSession $skillSession): bool
    {
        $swapRequest = $skillSession->swapRequest;

        return $user->role === 'user'
            && $user->onboarding_completed
            && $swapRequest->status === SwapRequest::STATUS_ACCEPTED
            && ($user->id === $swapRequest->sender_id
                || $user->id === $swapRequest->recipient_id)
            && $user->id !== $skillSession->scheduled_by;
    }

    public function confirmCompletion(User $user, SkillSession $skillSession): bool
    {
        $swapRequest = $skillSession->swapRequest;

        return $user->role === 'user'
            && $user->onboarding_completed
            && $swapRequest->status === SwapRequest::STATUS_ACCEPTED
            && ($user->id === $swapRequest->sender_id
                || $user->id === $swapRequest->recipient_id);
    }

    public function review(User $user, SkillSession $skillSession): bool
    {
        $swapRequest = $skillSession->swapRequest;

        return $user->role === 'user'
            && $user->onboarding_completed
            && $skillSession->status === SkillSession::STATUS_COMPLETED
            && $swapRequest->status === SwapRequest::STATUS_ACCEPTED
            && ($user->id === $swapRequest->sender_id
                || $user->id === $swapRequest->recipient_id);
    }
}
