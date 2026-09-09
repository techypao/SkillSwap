<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SwapChatController extends Controller
{
    public function show(Request $request, SwapRequest $swapRequest): View
    {
        Gate::authorize('chat', $swapRequest);

        $swapRequest->load([
            'sender',
            'recipient',
            'offeredSkill',
            'requestedSkill',
            'messages' => fn ($query) => $query->with('sender')
                ->orderBy('created_at')
                ->orderBy('id'),
            'skillSession.scheduledBy',
            'skillSession.reviews' => fn ($query) => $query->where('reviewer_id', $request->user()->id),
        ]);

        $currentUserIsSender = $request->user()->id === $swapRequest->sender_id;
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

        return view('swap-requests.chat', compact(
            'swapRequest',
            'currentUserConfirmedAt',
            'otherUserConfirmedAt',
            'currentUserReview',
            'otherUser',
            'skillYouTeach',
            'skillYouLearn'
        ));
    }
}
