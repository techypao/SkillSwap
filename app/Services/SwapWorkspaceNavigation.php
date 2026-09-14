<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\SkillSession;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the swap workspace navigation (active, pending and historical exchanges) for one user.
 *
 * Only the user's own swaps are queried, and only the columns needed for compact sidebar items are
 * loaded; message histories are never loaded here.
 */
class SwapWorkspaceNavigation
{
    public const HISTORY_LIMIT = 15;

    public const PENDING_LIMIT = 10;

    /**
     * @return array{
     *     active: Collection<int, array{swap: SwapRequest, other: User, teach: Skill, learn: Skill, stage: array{key: string, label: string}, selected: bool, url: string}>,
     *     history: Collection<int, array{swap: SwapRequest, other: User, teach: Skill, learn: Skill, stage: array{key: string, label: string}, selected: bool, url: string}>,
     *     pending: Collection<int, array{swap: SwapRequest, other: User, teach: Skill, learn: Skill, label: string, url: string}>
     * }
     */
    public function sidebarFor(User $user, SwapRequest $selectedSwapRequest): array
    {
        $acceptedSwaps = SwapRequest::query()
            ->where('status', SwapRequest::STATUS_ACCEPTED)
            ->where(fn (Builder $query) => $query->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
            ->with([
                'sender:id,name',
                'recipient:id,name',
                'offeredSkill:id,name',
                'requestedSkill:id,name',
                'skillSession:id,swap_request_id,status,scheduled_by,scheduled_at,duration_minutes,completed_at',
            ])
            ->withMax('messages', 'created_at')
            ->get();

        [$completedSwaps, $activeSwaps] = $acceptedSwaps->partition(
            fn (SwapRequest $swap): bool => $swap->skillSession?->status === SkillSession::STATUS_COMPLETED
        );

        $active = $activeSwaps
            ->sortByDesc(fn (SwapRequest $swap): string => max(
                (string) $swap->messages_max_created_at,
                (string) $swap->responded_at?->format('Y-m-d H:i:s'),
            ))
            ->values()
            ->map(fn (SwapRequest $swap): array => $this->acceptedItem($swap, $user, $selectedSwapRequest));

        $history = $completedSwaps
            ->sortByDesc(fn (SwapRequest $swap): string => (string) $swap->skillSession->completed_at?->format('Y-m-d H:i:s'))
            ->values()
            ->filter(fn (SwapRequest $swap, int $position): bool => $position < self::HISTORY_LIMIT
                || $swap->id === $selectedSwapRequest->id)
            ->values()
            ->map(fn (SwapRequest $swap): array => $this->acceptedItem($swap, $user, $selectedSwapRequest));

        return [
            'active' => $active,
            'history' => $history,
            'pending' => $this->pendingItems($user),
        ];
    }

    /**
     * Derive a display-only stage label from the existing session state.
     *
     * @return array{key: string, label: string}
     */
    public function sessionStage(?SkillSession $skillSession, int $currentUserId): array
    {
        [$key, $label] = match (true) {
            $skillSession === null,
            $skillSession->status === SkillSession::STATUS_CANCELLED => ['discussing', 'Discussing Schedule'],
            $skillSession->status === SkillSession::STATUS_COMPLETED => ['completed', 'Completed'],
            $skillSession->status === SkillSession::STATUS_PROPOSED && $skillSession->scheduled_by === $currentUserId => ['pending', 'Proposal Pending'],
            $skillSession->status === SkillSession::STATUS_PROPOSED => ['respond', 'Needs Your Response'],
            $skillSession->hasEnded() => ['awaiting', 'Awaiting Completion'],
            default => ['confirmed', 'Session Confirmed'],
        };

        return ['key' => $key, 'label' => $label];
    }

    /**
     * @return array{swap: SwapRequest, other: User, teach: Skill, learn: Skill, stage: array{key: string, label: string}, selected: bool, url: string}
     */
    private function acceptedItem(SwapRequest $swap, User $user, SwapRequest $selectedSwapRequest): array
    {
        $userIsSender = $swap->sender_id === $user->id;

        return [
            'swap' => $swap,
            'other' => $userIsSender ? $swap->recipient : $swap->sender,
            'teach' => $userIsSender ? $swap->offeredSkill : $swap->requestedSkill,
            'learn' => $userIsSender ? $swap->requestedSkill : $swap->offeredSkill,
            'stage' => $this->sessionStage($swap->skillSession, $user->id),
            'selected' => $swap->id === $selectedSwapRequest->id,
            'url' => route('swap-requests.chat', $swap),
        ];
    }

    /**
     * Pending requests link to the dashboard, where the existing accept/reject flow lives.
     *
     * @return Collection<int, array{swap: SwapRequest, other: User, teach: Skill, learn: Skill, label: string, url: string}>
     */
    private function pendingItems(User $user): Collection
    {
        $received = $user->receivedSwapRequests()
            ->where('status', SwapRequest::STATUS_PENDING)
            ->with(['sender:id,name', 'offeredSkill:id,name', 'requestedSkill:id,name'])
            ->latest()
            ->latest('id')
            ->take(self::PENDING_LIMIT)
            ->get()
            ->map(fn (SwapRequest $swap): array => [
                'swap' => $swap,
                'other' => $swap->sender,
                'teach' => $swap->requestedSkill,
                'learn' => $swap->offeredSkill,
                'label' => 'Request received',
                'url' => route('dashboard').'#incoming-requests',
            ]);

        $sent = $user->sentSwapRequests()
            ->where('status', SwapRequest::STATUS_PENDING)
            ->with(['recipient:id,name', 'offeredSkill:id,name', 'requestedSkill:id,name'])
            ->latest()
            ->latest('id')
            ->take(self::PENDING_LIMIT)
            ->get()
            ->map(fn (SwapRequest $swap): array => [
                'swap' => $swap,
                'other' => $swap->recipient,
                'teach' => $swap->offeredSkill,
                'learn' => $swap->requestedSkill,
                'label' => 'Request sent',
                'url' => route('dashboard').'#sent-requests',
            ]);

        return $received->concat($sent)
            ->sortByDesc(fn (array $item): string => $item['swap']->created_at->format('Y-m-d H:i:s'))
            ->values();
    }
}
