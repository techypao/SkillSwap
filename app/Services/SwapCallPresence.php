<?php

namespace App\Services;

use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks which swap participants currently have the call open.
 *
 * A participant is present while their browser keeps refreshing the entry
 * (join + in-call polling) within the configured presence window.
 */
class SwapCallPresence
{
    public function markPresent(SwapRequest $swapRequest, User $user): void
    {
        Cache::put(
            $this->key($swapRequest, $user),
            now()->getTimestamp(),
            now()->addSeconds((int) config('webrtc.presence_ttl_seconds'))
        );
    }

    public function markAbsent(SwapRequest $swapRequest, User $user): void
    {
        Cache::forget($this->key($swapRequest, $user));
    }

    public function isPresent(SwapRequest $swapRequest, User $user): bool
    {
        return Cache::has($this->key($swapRequest, $user));
    }

    private function key(SwapRequest $swapRequest, User $user): string
    {
        return "swap-call-presence:{$swapRequest->id}:{$user->id}";
    }
}
