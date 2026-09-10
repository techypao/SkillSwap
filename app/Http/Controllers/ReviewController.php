<?php

namespace App\Http\Controllers;

use App\Models\SkillSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(Request $request, SkillSession $skillSession): View|RedirectResponse
    {
        Gate::authorize('review', $skillSession);

        $skillSession->load(['swapRequest.sender', 'swapRequest.recipient']);

        if ($skillSession->reviews()->where('reviewer_id', $request->user()->id)->exists()) {
            return redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'You have already reviewed this skill swap.');
        }

        $currentUserIsSender = $request->user()->id === $skillSession->swapRequest->sender_id;
        $reviewee = $currentUserIsSender ? $skillSession->swapRequest->recipient : $skillSession->swapRequest->sender;

        return view('reviews.create', compact('skillSession', 'reviewee'));
    }

    public function store(Request $request, SkillSession $skillSession): RedirectResponse
    {
        Gate::authorize('review', $skillSession);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $skillSession->load('swapRequest');
        $revieweeId = $request->user()->id === $skillSession->swapRequest->sender_id
            ? $skillSession->swapRequest->recipient_id
            : $skillSession->swapRequest->sender_id;

        $review = $skillSession->reviews()->firstOrCreate(
            ['reviewer_id' => $request->user()->id],
            [
                'reviewee_id' => $revieweeId,
                'rating' => $validated['rating'],
                'comment' => isset($validated['comment']) ? trim($validated['comment']) : null,
            ]
        );

        if (! $review->wasRecentlyCreated) {
            return redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'You have already reviewed this skill swap.');
        }

        return redirect()
            ->route('swap-requests.chat', $skillSession->swap_request_id)
            ->with('success', 'Review submitted!');
    }
}
