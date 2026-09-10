<?php

namespace App\Http\Controllers;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->refresh();

        $user->load([
            'program',
            'teachingSkills.category',
            'learningSkills.category',
            'availabilities',
        ]);

        $recommendedMatches = collect();

        $swapRequests = $user->receivedSwapRequests()
            ->where('status', SwapRequest::STATUS_PENDING)
            ->with(['sender', 'offeredSkill', 'requestedSkill'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $sentSwapRequests = $user->sentSwapRequests()
            ->with(['recipient', 'offeredSkill', 'requestedSkill'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $acceptedSwapRequests = SwapRequest::query()
            ->where('status', SwapRequest::STATUS_ACCEPTED)
            ->where(function (Builder $query) use ($user): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->whereDoesntHave('skillSession', function (Builder $query): void {
                $query->where('status', SkillSession::STATUS_COMPLETED);
            })
            ->with(['sender', 'recipient', 'offeredSkill', 'requestedSkill', 'skillSession'])
            ->orderByDesc('responded_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $upcomingSessions = SkillSession::query()
            ->where('status', SkillSession::STATUS_CONFIRMED)
            ->where('scheduled_at', '>', now())
            ->whereHas('swapRequest', function (Builder $query) use ($user): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->with([
                'swapRequest.sender',
                'swapRequest.recipient',
                'swapRequest.offeredSkill',
                'swapRequest.requestedSkill',
            ])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->take(5)
            ->get();

        $awaitingCompletionSessions = SkillSession::query()
            ->where('status', SkillSession::STATUS_CONFIRMED)
            ->where('scheduled_at', '<=', now())
            ->whereHas('swapRequest', function (Builder $query) use ($user): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->with(['swapRequest.sender', 'swapRequest.recipient'])
            ->orderByDesc('scheduled_at')
            ->take(5)
            ->get();

        $completedSwapRequests = SwapRequest::query()
            ->where('status', SwapRequest::STATUS_ACCEPTED)
            ->where(function (Builder $query) use ($user): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->whereHas('skillSession', function (Builder $query): void {
                $query->where('status', SkillSession::STATUS_COMPLETED);
            })
            ->with([
                'sender',
                'recipient',
                'offeredSkill',
                'requestedSkill',
                'skillSession.reviews' => fn ($query) => $query->where('reviewer_id', $user->id),
            ])
            ->orderByDesc(
                SkillSession::query()
                    ->select('completed_at')
                    ->whereColumn('skill_sessions.swap_request_id', 'swap_requests.id')
                    ->limit(1)
            )
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'user',
            'recommendedMatches',
            'swapRequests',
            'sentSwapRequests',
            'acceptedSwapRequests',
            'upcomingSessions',
            'awaitingCompletionSessions',
            'completedSwapRequests'
        ));
    }
}
