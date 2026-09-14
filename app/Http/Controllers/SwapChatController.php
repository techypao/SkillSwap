<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\UserAvailability;
use App\Services\SwapWorkspaceNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SwapChatController extends Controller
{
    /**
     * @var list<string>
     */
    private const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /**
     * @var list<string>
     */
    private const TIME_PERIOD_ORDER = ['morning', 'afternoon', 'evening'];

    public function show(Request $request, SwapRequest $swapRequest, SwapWorkspaceNavigation $navigation): View
    {
        Gate::authorize('chat', $swapRequest);

        $swapRequest->load([
            'sender.program',
            'sender.availabilities',
            'recipient.program',
            'recipient.availabilities',
            'offeredSkill',
            'requestedSkill',
            'messages' => fn ($query) => $query->with('sender')
                ->orderBy('created_at')
                ->orderBy('id'),
            'skillSession.scheduledBy',
            'skillSession.reviews' => fn ($query) => $query->where('reviewer_id', $request->user()->id),
        ]);
        $swapRequest->skillSession?->setRelation('swapRequest', $swapRequest);

        $currentUserIsSender = $request->user()->id === $swapRequest->sender_id;
        $currentUser = $currentUserIsSender ? $swapRequest->sender : $swapRequest->recipient;
        $otherUser = $currentUserIsSender ? $swapRequest->recipient : $swapRequest->sender;
        $skillYouTeach = $currentUserIsSender ? $swapRequest->offeredSkill : $swapRequest->requestedSkill;
        $skillYouLearn = $currentUserIsSender ? $swapRequest->requestedSkill : $swapRequest->offeredSkill;
        $currentUserConfirmedAt = $currentUserIsSender
            ? $swapRequest->skillSession?->sender_confirmed_at
            : $swapRequest->skillSession?->recipient_confirmed_at;
        $otherUserConfirmedAt = $currentUserIsSender
            ? $swapRequest->skillSession?->recipient_confirmed_at
            : $swapRequest->skillSession?->sender_confirmed_at;
        $currentUserReview = $swapRequest->skillSession?->reviews->first();

        $currentUserAvailabilities = $this->sortAvailabilities($currentUser->availabilities);
        $otherUserAvailabilities = $this->sortAvailabilities($otherUser->availabilities);
        $otherUserAvailabilityKeys = $otherUserAvailabilities
            ->map(fn (UserAvailability $availability): string => $availability->day.'_'.$availability->time_period);
        $commonAvailabilities = $currentUserAvailabilities
            ->filter(fn (UserAvailability $availability): bool => $otherUserAvailabilityKeys
                ->contains($availability->day.'_'.$availability->time_period))
            ->values();

        $sessionStage = $navigation->sessionStage($swapRequest->skillSession, $request->user()->id);
        $swapSidebar = $navigation->sidebarFor($request->user(), $swapRequest);

        $canCall = $request->user()->can('call', $swapRequest);
        $isCallOfferer = $currentUserIsSender;
        $latestCallSignalId = $canCall ? (int) $swapRequest->callSignals()->max('id') : 0;

        return view('swap-requests.chat', compact(
            'swapRequest',
            'currentUserConfirmedAt',
            'otherUserConfirmedAt',
            'currentUserReview',
            'otherUser',
            'skillYouTeach',
            'skillYouLearn',
            'currentUserAvailabilities',
            'otherUserAvailabilities',
            'commonAvailabilities',
            'sessionStage',
            'swapSidebar',
            'canCall',
            'isCallOfferer',
            'latestCallSignalId'
        ));
    }

    /**
     * Sort availability records by weekday, then by time of day.
     *
     * @param  Collection<int, UserAvailability>  $availabilities
     * @return Collection<int, UserAvailability>
     */
    private function sortAvailabilities(Collection $availabilities): Collection
    {
        return $availabilities
            ->sortBy(fn (UserAvailability $availability): int => array_search($availability->day, self::DAY_ORDER, true) * 10
                + (int) array_search($availability->time_period, self::TIME_PERIOD_ORDER, true))
            ->values();
    }
}
