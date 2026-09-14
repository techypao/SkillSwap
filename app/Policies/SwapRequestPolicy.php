<?php

namespace App\Policies;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;

class SwapRequestPolicy
{
    public function respond(User $user, SwapRequest $swapRequest): bool
    {
        return $user->role === 'user'
            && $user->onboarding_completed
            && $user->id === $swapRequest->recipient_id;
    }

    public function schedule(User $user, SwapRequest $swapRequest): bool
    {
        return $user->role === 'user'
            && $user->onboarding_completed
            && ($user->id === $swapRequest->sender_id
                || $user->id === $swapRequest->recipient_id);
    }

    public function chat(User $user, SwapRequest $swapRequest): bool
    {
        return $swapRequest->status === SwapRequest::STATUS_ACCEPTED
            && $this->schedule($user, $swapRequest);
    }

    /**
     * Voice/video calls open once the swap's session is confirmed and close when it completes.
     */
    public function call(User $user, SwapRequest $swapRequest): bool
    {
        return $this->chat($user, $swapRequest)
            && $swapRequest->skillSession?->status === SkillSession::STATUS_CONFIRMED;
    }
}
