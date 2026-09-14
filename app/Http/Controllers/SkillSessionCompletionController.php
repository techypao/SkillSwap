<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\SkillSession;
use App\Models\User;
use App\Notifications\CompletionConfirmationRequested;
use App\Notifications\SkillSwapCompleted;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SkillSessionCompletionController extends Controller
{
    public function __invoke(Request $request, SkillSession $skillSession): RedirectResponse
    {
        Gate::authorize('confirmCompletion', $skillSession);

        $userId = (int) auth()->id();

        try {
            $result = DB::transaction(function () use ($skillSession, $userId): string {
                $lockedSession = SkillSession::query()
                    ->with(['swapRequest.sender', 'swapRequest.recipient'])
                    ->lockForUpdate()
                    ->findOrFail($skillSession->id);

                if ($lockedSession->status === SkillSession::STATUS_COMPLETED) {
                    return 'already_completed';
                }

                if ($lockedSession->status !== SkillSession::STATUS_CONFIRMED) {
                    return 'not_confirmed';
                }

                if ($lockedSession->endsAt() === null) {
                    return 'invalid_schedule';
                }

                if (! $lockedSession->hasEnded()) {
                    return 'too_early';
                }

                $confirmationColumn = $userId === $lockedSession->swapRequest->sender_id
                    ? 'sender_confirmed_at'
                    : 'recipient_confirmed_at';

                if ($lockedSession->{$confirmationColumn} !== null) {
                    return 'already_confirmed';
                }

                $otherConfirmationColumn = $confirmationColumn === 'sender_confirmed_at'
                    ? 'recipient_confirmed_at'
                    : 'sender_confirmed_at';

                if ($lockedSession->{$otherConfirmationColumn} === null) {
                    $lockedSession->{$confirmationColumn} = now();
                    $lockedSession->save();

                    return 'waiting';
                }

                $teacher = $lockedSession->teacher;
                $learner = $lockedSession->learner;

                if ($teacher === null || $learner === null) {
                    return 'unresolved_roles';
                }

                $participants = User::query()
                    ->whereKey([$teacher->id, $learner->id])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $lockedTeacher = $participants->get($teacher->id);
                $lockedLearner = $participants->get($learner->id);

                if (! $lockedTeacher instanceof User || ! $lockedLearner instanceof User) {
                    return 'unresolved_roles';
                }

                $settlementAlreadyExists = CreditTransaction::query()
                    ->where('skill_session_id', $lockedSession->id)
                    ->whereIn('reason', [
                        CreditTransaction::REASON_SESSION_TAUGHT,
                        CreditTransaction::REASON_SESSION_LEARNED,
                    ])
                    ->lockForUpdate()
                    ->exists();

                if ($settlementAlreadyExists) {
                    return 'settlement_failed';
                }

                if ($lockedLearner->skill_credits < 1) {
                    return 'insufficient_credits';
                }

                $learnerDebited = User::query()
                    ->whereKey($lockedLearner->id)
                    ->where('skill_credits', '>=', 1)
                    ->decrement('skill_credits');

                if ($learnerDebited !== 1) {
                    return 'insufficient_credits';
                }

                CreditTransaction::create([
                    'user_id' => $lockedTeacher->id,
                    'skill_session_id' => $lockedSession->id,
                    'amount' => 1,
                    'reason' => CreditTransaction::REASON_SESSION_TAUGHT,
                ]);
                CreditTransaction::create([
                    'user_id' => $lockedLearner->id,
                    'skill_session_id' => $lockedSession->id,
                    'amount' => -1,
                    'reason' => CreditTransaction::REASON_SESSION_LEARNED,
                ]);

                User::query()->whereKey($lockedTeacher->id)->increment('skill_credits');

                $lockedSession->update([
                    $confirmationColumn => now(),
                    'status' => SkillSession::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                return 'completed';
            }, 5);
        } catch (QueryException $exception) {
            report($exception);

            $result = 'settlement_failed';
        }

        if ($result === 'waiting' || $result === 'completed') {
            $notification = $result === 'waiting'
                ? new CompletionConfirmationRequested($request->user(), $skillSession->swap_request_id)
                : new SkillSwapCompleted($request->user(), $skillSession->swap_request_id);

            $skillSession->swapRequest->otherParticipantFor($request->user())->notify($notification);
        }

        return match ($result) {
            'waiting' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('success', 'You confirmed completion. Waiting for the other participant.'),
            'completed' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('success', 'Skill swap completed! The teacher earned 1 Skill Credit and the learner spent 1 Skill Credit.'),
            'insufficient_credits' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'The learner no longer has enough Skill Credits to complete this session.'),
            'unresolved_roles' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'This session cannot settle Skill Credits because its teacher or learner could not be determined.'),
            'settlement_failed' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'Skill Credits could not be settled. Please try again.'),
            'too_early' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'This session can only be marked complete after it has ended.'),
            'invalid_schedule' => redirect()
                ->route('swap-requests.chat', $skillSession->swap_request_id)
                ->with('info', 'This session cannot be marked complete because its schedule is missing or invalid.'),
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
