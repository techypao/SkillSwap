<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\SkillSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SkillSessionCompletionController extends Controller
{
    public function __invoke(SkillSession $skillSession): RedirectResponse
    {
        Gate::authorize('confirmCompletion', $skillSession);

        $userId = (int) auth()->id();

        $result = DB::transaction(function () use ($skillSession, $userId): string {
            $lockedSession = SkillSession::query()
                ->with('swapRequest')
                ->lockForUpdate()
                ->findOrFail($skillSession->id);

            if ($lockedSession->status === SkillSession::STATUS_COMPLETED) {
                return 'already_completed';
            }

            if ($lockedSession->status !== SkillSession::STATUS_CONFIRMED) {
                return 'not_confirmed';
            }

            if ($lockedSession->scheduled_at->isFuture()) {
                return 'too_early';
            }

            $confirmationColumn = $userId === $lockedSession->swapRequest->sender_id
                ? 'sender_confirmed_at'
                : 'recipient_confirmed_at';

            if ($lockedSession->{$confirmationColumn} !== null) {
                return 'already_confirmed';
            }

            $lockedSession->{$confirmationColumn} = now();
            $lockedSession->save();

            if ($lockedSession->sender_confirmed_at === null
                || $lockedSession->recipient_confirmed_at === null) {
                return 'waiting';
            }

            $lockedSession->update([
                'status' => SkillSession::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            foreach ([$lockedSession->swapRequest->sender_id, $lockedSession->swapRequest->recipient_id] as $participantId) {
                $creditTransaction = CreditTransaction::firstOrCreate(
                    [
                        'user_id' => $participantId,
                        'skill_session_id' => $lockedSession->id,
                        'reason' => CreditTransaction::REASON_SESSION_COMPLETED,
                    ],
                    ['amount' => 1]
                );

                if ($creditTransaction->wasRecentlyCreated) {
                    User::query()->whereKey($participantId)->increment('skill_credits');
                }
            }

            return 'completed';
        }, 5);

        return match ($result) {
            'waiting' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('success', 'You confirmed completion. Waiting for the other participant.'),
            'completed' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('success', 'Skill swap completed! You each earned +1 Skill Credit.'),
            'too_early' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'Completion can be confirmed after the session starts.'),
            'already_confirmed' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'You have already confirmed completion.'),
            'already_completed' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'This skill swap is already completed.'),
            default => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'Only a confirmed session can be completed.'),
        };
    }
}
